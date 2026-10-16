/*
 * Yoast-style analysis of a blog post, in the browser.
 *
 * Every check runs on the text as the writer types, so nothing is saved and
 * no request is made. The two scores are the same pair Yoast shows: an SEO
 * score built from how the focus keyphrase is used, and a readability score
 * built from sentence and paragraph shape.
 *
 * The scoring follows Yoast's own arithmetic: each check contributes up to
 * nine points, the total is expressed as a percentage, and the traffic light
 * turns green above 70 and red below 40.
 *
 * Caveat worth knowing: readability measures are built for English prose, so
 * a post heavy with section numbers, rupee figures and statute references
 * will read as harder than it is. Treat those bullets as a nudge, not a rule.
 */
(function (global) {
    'use strict';

    var GOOD = 'good', OK = 'ok', BAD = 'bad', OFF = 'off';
    var POINTS = { good: 9, ok: 6, bad: 3 };

    // Words that carry no search intent. Yoast strips them before judging how
    // long a keyphrase is, so "the tax on rental income" counts as three.
    var FUNCTION_WORDS = ('a,an,the,and,or,but,if,then,than,so,of,on,in,at,to,for,from,by,with,without,about,' +
        'as,is,are,was,were,be,been,being,do,does,did,have,has,had,it,its,this,that,these,those,you,your,' +
        'we,our,i,my,he,she,they,their,what,which,who,how,when,where,why,can,could,should,would,will,shall,' +
        'may,might,must,not,no,all,any,more,most,other,some,such,only,own,same,too,very,s,t,just,up,down,out'
    ).split(',');

    var TRANSITIONS = ('accordingly,additionally,afterwards,also,although,altogether,another,as a result,' +
        'as well as,because,before,besides,but,by contrast,certainly,comparatively,consequently,' +
        'conversely,correspondingly,despite,earlier,equally,especially,even so,eventually,finally,' +
        'first,firstly,for example,for instance,for this reason,further,furthermore,generally,hence,' +
        'however,in addition,in brief,in contrast,in fact,in other words,in particular,in practice,' +
        'in short,in summary,in the same way,indeed,instead,later,likewise,meanwhile,moreover,' +
        'namely,nevertheless,next,nonetheless,notably,obviously,on the contrary,on the other hand,' +
        'once,otherwise,overall,particularly,previously,rather,second,secondly,similarly,since,' +
        'so that,specifically,still,subsequently,such as,that is,then,thereafter,therefore,' +
        'third,thirdly,though,thus,to summarise,to summarize,ultimately,unless,unlike,until,' +
        'what is more,whereas,while,yet'
    ).split(',');

    // Past participles that do not end in -ed, for spotting the passive.
    var IRREGULAR = ('been,begun,blown,broken,brought,built,bought,caught,chosen,come,cut,done,drawn,driven,' +
        'eaten,fallen,felt,fought,found,given,gone,grown,heard,held,kept,known,laid,led,left,lent,let,' +
        'lost,made,meant,met,paid,put,read,run,said,seen,sent,set,shown,shut,sold,sought,spent,split,' +
        'spoken,spread,stood,taken,taught,thought,told,understood,won,withheld,written'
    ).split(',');

    /* ---------- text extraction ---------- */

    function blocks(html) {
        var holder = document.createElement('div');
        holder.innerHTML = html || '';
        holder.querySelectorAll('figcaption, table, script, style').forEach(function (n) { n.remove(); });

        var out = [];
        holder.querySelectorAll('p, li, h1, h2, h3, h4, blockquote').forEach(function (n) {
            var text = (n.textContent || '').replace(/\s+/g, ' ').trim();
            if (text) { out.push({ tag: n.tagName.toLowerCase(), text: text }); }
        });

        // A body typed straight into the editor may have no block tags at all.
        if (!out.length) {
            var plain = (holder.textContent || '').replace(/\s+/g, ' ').trim();
            if (plain) { out.push({ tag: 'p', text: plain }); }
        }

        return out;
    }

    function wordsIn(text) {
        return (text.toLowerCase().match(/[\p{L}\p{N}][\p{L}\p{N}'’_-]*/gu) || []);
    }

    function sentencesIn(text) {
        // Protect the abbreviations and figures that pepper tax writing, so
        // "Rs. 1.5m under s. 236C" is not read as four sentences.
        var guarded = text
            .replace(/\b(Rs|No|Mr|Mrs|Ms|Dr|St|vs|etc|i\.e|e\.g|s|ss|u\/s|Sec|Art|Ltd|Pvt|Inc)\.\s/gi, '$1<dot> ')
            .replace(/(\d)\.(\d)/g, '$1<dot>$2');

        return guarded.split(/(?<=[.!?])\s+/)
            .map(function (s) { return s.replace(/<dot>/g, '.').trim(); })
            .filter(function (s) { return wordsIn(s).length > 0; });
    }

    function escapeRx(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /** Count a phrase in text, tolerating any run of spaces or punctuation between its words. */
    function countPhrase(text, phrase) {
        var parts = wordsIn(phrase);
        if (!parts.length) { return 0; }

        var pattern = parts.map(escapeRx).join('[\\s\\-–—,:;’\'"()]+');
        var matches = text.toLowerCase().match(new RegExp('(?<![\\p{L}\\p{N}])' + pattern + '(?![\\p{L}\\p{N}])', 'giu'));

        return matches ? matches.length : 0;
    }

    function hasPhrase(text, phrase) {
        return countPhrase(text || '', phrase) > 0;
    }

    function syllables(word) {
        word = word.toLowerCase().replace(/[^a-z]/g, '');
        if (!word) { return 0; }
        if (word.length <= 3) { return 1; }

        word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '').replace(/^y/, '');
        var groups = word.match(/[aeiouy]{1,2}/g);

        return groups ? groups.length : 1;
    }

    /** Rough pixel width, the way Yoast judges whether a snippet will be cut off. */
    var canvas;
    function pixels(text, font) {
        if (!canvas) { canvas = document.createElement('canvas'); }
        var ctx = canvas.getContext('2d');
        ctx.font = font;

        return Math.round(ctx.measureText(text || '').width);
    }

    /* ---------- the checks ---------- */

    function seoChecks(input, text) {
        var checks = [];
        var phrase = (input.keyphrase || '').trim();
        var synonyms = (input.synonyms || []).filter(Boolean);
        var seoTitle = (input.metaTitle || input.title || '').trim();
        var description = (input.metaDescription || input.excerpt || '').trim();
        var body = text.body;
        var add = function (id, status, label) { checks.push({ id: id, status: status, text: label }); };

        /* --- the snippet, judged with or without a keyphrase --- */

        var titleWidth = pixels(seoTitle, '700 20px Arial');
        if (!seoTitle) {
            add('titleWidth', BAD, 'No SEO title. Google will invent one from the page.');
        } else if (titleWidth > 600) {
            add('titleWidth', BAD, 'The SEO title is too wide (' + titleWidth + 'px of about 600) and will be cut off.');
        } else if (titleWidth < 400) {
            add('titleWidth', OK, 'The SEO title is short (' + titleWidth + 'px of about 600). There is room to say more.');
        } else {
            add('titleWidth', GOOD, 'The SEO title is a good width.');
        }

        var descLength = description.length;
        if (!descLength) {
            add('metaLength', BAD, 'No meta description. Google will pull a sentence from the page instead.');
        } else if (descLength > 156) {
            add('metaLength', BAD, 'The meta description runs to ' + descLength + ' characters and will be cut off near 156.');
        } else if (descLength < 120) {
            add('metaLength', OK, 'The meta description is ' + descLength + ' characters. Between 120 and 156 uses the whole space.');
        } else {
            add('metaLength', GOOD, 'The meta description is a good length.');
        }

        /* --- length, links and images --- */

        var count = body.words.length;
        if (count < 300) {
            add('textLength', BAD, 'The post is ' + count + ' words. Under 300 rarely ranks for anything.');
        } else if (count < 600) {
            add('textLength', OK, 'The post is ' + count + ' words. Competitive tax queries usually need 900 or more.');
        } else if (count < 900) {
            add('textLength', OK, 'The post is ' + count + ' words — respectable, though 900+ wins more often on advice topics.');
        } else {
            add('textLength', GOOD, 'The post is ' + count + ' words. Good length.');
        }

        var links = text.links;
        if (!links.outbound) {
            add('outboundLinks', OK, 'No links out. Citing FBR, the Ordinance or a bank page lends the piece authority.');
        } else {
            add('outboundLinks', GOOD, links.outbound + ' link' + (links.outbound === 1 ? '' : 's') + ' out to other sites.');
        }

        if (!links.internal) {
            add('internalLinks', BAD, 'No links to your own pages. Internal links pass ranking and keep readers on the site.');
        } else {
            add('internalLinks', GOOD, links.internal + ' internal link' + (links.internal === 1 ? '' : 's') + '.');
        }

        var images = text.images;
        if (!images.total) {
            add('images', BAD, 'No images in the body. A screenshot of the portal or form helps a how-to enormously.');
        } else if (images.missingAlt) {
            add('images', OK, images.total + ' image' + (images.total === 1 ? '' : 's') + ', but ' + images.missingAlt + ' without alt text.');
        } else {
            add('images', GOOD, images.total + ' image' + (images.total === 1 ? '' : 's') + ', all with alt text.');
        }

        if (!input.heroImage) {
            add('heroImage', OK, 'No hero image. It is what shows when the post is shared.');
        } else if (!input.heroAlt) {
            add('heroImage', OK, 'The hero image has no alt text.');
        } else {
            add('heroImage', GOOD, 'Hero image set, with alt text.');
        }

        /* --- everything below needs a keyphrase --- */

        if (!phrase) {
            checks.unshift({ id: 'keyphrase', status: OFF,
                text: 'No focus keyphrase set. Enter the search you want this post to win and the rest of the analysis appears.' });

            return checks;
        }

        var contentWords = wordsIn(phrase).filter(function (w) { return FUNCTION_WORDS.indexOf(w) === -1; });
        if (contentWords.length === 0) {
            add('keyphraseLength', BAD, 'The keyphrase is made only of common words. Nobody searches for those alone.');
        } else if (contentWords.length <= 4) {
            add('keyphraseLength', GOOD, 'The keyphrase is a good length.');
        } else if (contentWords.length <= 6) {
            add('keyphraseLength', OK, 'The keyphrase is ' + contentWords.length + ' words. Four or fewer is easier to rank.');
        } else {
            add('keyphraseLength', BAD, 'The keyphrase is ' + contentWords.length + ' words — too long to rank as a phrase.');
        }

        var titleWords = wordsIn(seoTitle);
        var firstHalf = titleWords.slice(0, Math.ceil(titleWords.length / 2)).join(' ');
        if (!hasPhrase(seoTitle, phrase)) {
            add('keyphraseInTitle', BAD, 'The keyphrase is not in the SEO title. It belongs there.');
        } else if (hasPhrase(firstHalf, phrase)) {
            add('keyphraseInTitle', GOOD, 'The keyphrase is in the SEO title, near the front.');
        } else {
            add('keyphraseInTitle', OK, 'The keyphrase is in the SEO title, but late. Earlier carries more weight.');
        }

        add('keyphraseInMeta', hasPhrase(description, phrase) ? GOOD : BAD,
            hasPhrase(description, phrase)
                ? 'The keyphrase is in the meta description.'
                : 'The keyphrase is missing from the meta description — Google bolds it in the result.');

        var slugHit = hasPhrase((input.slug || '').replace(/-/g, ' '), phrase);
        add('keyphraseInSlug', slugHit ? GOOD : BAD,
            slugHit ? 'The keyphrase is in the slug.' : 'The keyphrase is not in the slug.');

        var intro = body.paragraphs.length ? body.paragraphs[0].text : '';
        var introHit = hasPhrase(intro, phrase) || hasPhrase(body.words.slice(0, 120).join(' '), phrase);
        add('keyphraseInIntro', introHit ? GOOD : BAD,
            introHit ? 'The keyphrase appears in the opening.' : 'The keyphrase is not in the first paragraph. Say it early.');

        var subs = body.headings;
        if (!subs.length) {
            add('keyphraseInSubheadings', BAD, 'There are no subheadings at all. Break the post up with H2s.');
        } else {
            var hit = subs.filter(function (h) { return hasPhrase(h.text, phrase); }).length;
            var share = hit / subs.length;
            if (!hit) {
                add('keyphraseInSubheadings', BAD, 'The keyphrase is in none of the ' + subs.length + ' subheadings.');
            } else if (share > 0.75) {
                add('keyphraseInSubheadings', OK, 'The keyphrase is in ' + hit + ' of ' + subs.length + ' subheadings — that reads as stuffing.');
            } else {
                add('keyphraseInSubheadings', GOOD, 'The keyphrase is in ' + hit + ' of ' + subs.length + ' subheadings.');
            }
        }

        if (images.total) {
            var altHit = images.alts.some(function (a) { return hasPhrase(a, phrase); });
            add('keyphraseInAlt', altHit ? GOOD : OK,
                altHit ? 'The keyphrase appears in an image alt text.'
                       : 'No image alt text mentions the keyphrase.');
        }

        var occurrences = countPhrase(body.text, phrase);
        synonyms.forEach(function (s) { occurrences += countPhrase(body.text, s) * 0.5; });
        // Yoast's arithmetic: uses of the phrase against total words, not
        // words of the phrase against total words. A four-word keyphrase
        // would otherwise look four times as dense as it is.
        var density = count ? (occurrences / count) * 100 : 0;
        var shown = density.toFixed(1) + '%';

        if (occurrences === 0) {
            add('density', BAD, 'The keyphrase does not appear in the body at all.');
        } else if (density > 3.5) {
            add('density', BAD, 'Keyphrase density ' + shown + ' over ' + occurrences + ' uses — that is over-optimised.');
        } else if (density > 3) {
            add('density', OK, 'Keyphrase density ' + shown + '. Slightly heavy; 0.5–3% reads naturally.');
        } else if (density < 0.5) {
            add('density', OK, 'Keyphrase density ' + shown + ' over ' + occurrences + ' use' + (occurrences === 1 ? '' : 's') + '. A little more would help.');
        } else {
            add('density', GOOD, 'Keyphrase density ' + shown + ' over ' + occurrences + ' uses.');
        }

        if (occurrences > 0 && count > 300) {
            var thirds = [0, 1, 2].map(function (i) {
                return body.words.slice(Math.floor(count * i / 3), Math.floor(count * (i + 1) / 3)).join(' ');
            });
            var covered = thirds.filter(function (t) { return hasPhrase(t, phrase); }).length;
            add('distribution', covered === 3 ? GOOD : covered === 2 ? OK : BAD,
                covered === 3 ? 'The keyphrase is spread through the whole post.'
                              : 'The keyphrase sits in only ' + covered + ' of the three parts of the post.');
        }

        var clash = (input.usedKeyphrases || []).filter(function (u) {
            return u.phrase === phrase.toLowerCase();
        });
        if (clash.length) {
            add('uniqueKeyphrase', BAD, 'Already the keyphrase of "' + clash[0].title + '". Two of your pages would compete for the same search.');
        } else {
            add('uniqueKeyphrase', GOOD, 'This keyphrase is not used by another post.');
        }

        return checks;
    }

    function readabilityChecks(text) {
        var checks = [];
        var body = text.body;
        var add = function (id, status, label) { checks.push({ id: id, status: status, text: label }); };

        if (body.words.length < 50) {
            return [{ id: 'tooShort', status: OFF, text: 'Too little text yet to judge readability.' }];
        }

        var sentences = body.sentences;
        var totalWords = body.words.length;
        var totalSyllables = body.words.reduce(function (n, w) { return n + syllables(w); }, 0);
        var flesch = 206.835 - 1.015 * (totalWords / Math.max(1, sentences.length)) - 84.6 * (totalSyllables / totalWords);
        flesch = Math.max(0, Math.min(100, Math.round(flesch)));

        var band = flesch >= 60 ? GOOD : flesch >= 50 ? OK : BAD;
        var describe = flesch >= 70 ? 'easy' : flesch >= 60 ? 'fairly easy' : flesch >= 50 ? 'fairly difficult' : 'difficult';
        add('flesch', band, 'Flesch reading ease ' + flesch + ' — ' + describe + ' to read.');

        var longSentences = sentences.filter(function (s) { return wordsIn(s).length > 20; });
        var longShare = Math.round(longSentences.length / sentences.length * 100);
        add('sentenceLength', longShare <= 25 ? GOOD : longShare <= 35 ? OK : BAD,
            longShare + '% of sentences run past 20 words' + (longShare > 25 ? ' — aim for 25% or less.' : '.'));

        var longParas = body.paragraphs.filter(function (p) { return wordsIn(p.text).length > 150; });
        add('paragraphLength', !longParas.length ? GOOD : longParas.length === 1 ? OK : BAD,
            !longParas.length ? 'No paragraph is over-long.'
                              : longParas.length + ' paragraph' + (longParas.length === 1 ? ' runs' : 's run') + ' past 150 words.');

        if (totalWords < 300) {
            add('subheadings', OFF, 'The post is short enough not to need subheadings.');
        } else if (!body.headings.length) {
            add('subheadings', BAD, 'No subheadings. A reader scanning for their answer has nothing to scan.');
        } else {
            var worst = Math.max.apply(null, body.sections.map(function (s) { return s.words; }));
            add('subheadings', worst <= 300 ? GOOD : BAD,
                worst <= 300 ? 'No stretch of text runs too long without a subheading.'
                             : 'One stretch runs ' + worst + ' words without a subheading.');
        }

        var runs = 0, previous = null, streak = 0;
        sentences.forEach(function (s) {
            var first = (wordsIn(s)[0] || '');
            if (first && first === previous) { streak++; if (streak === 2) { runs++; } } else { streak = 0; }
            previous = first;
        });
        add('consecutive', runs ? BAD : GOOD,
            runs ? runs + ' place' + (runs === 1 ? '' : 's') + ' where three sentences in a row start with the same word.'
                 : 'Sentence openings vary.');

        var passive = sentences.filter(isPassive).length;
        var passiveShare = Math.round(passive / sentences.length * 100);
        add('passive', passiveShare <= 10 ? GOOD : passiveShare <= 15 ? OK : BAD,
            passiveShare + '% of sentences are passive' + (passiveShare > 10 ? ' — 10% or less reads more directly.' : '.'));

        var withTransition = sentences.filter(function (s) {
            var lower = ' ' + s.toLowerCase() + ' ';

            return TRANSITIONS.some(function (t) {
                return new RegExp('(^|[\\s,;:.])' + escapeRx(t) + '([\\s,;:.]|$)').test(lower);
            });
        }).length;
        var transitionShare = Math.round(withTransition / sentences.length * 100);
        add('transitions', transitionShare >= 30 ? GOOD : transitionShare >= 20 ? OK : BAD,
            transitionShare + '% of sentences carry a transition word' +
            (transitionShare < 30 ? ' — 30% makes an argument easier to follow.' : '.'));

        return checks;
    }

    function isPassive(sentence) {
        var be = '(?:am|is|are|was|were|be|been|being|gets|get|got)';
        var participle = '(?:\\w+ed|' + IRREGULAR.join('|') + ')';

        return new RegExp('\\b' + be + '\\b(?:\\s+\\w+ly)?\\s+' + participle + '\\b', 'i').test(sentence);
    }

    /* ---------- assembling the input ---------- */

    function readBody(html) {
        var holder = document.createElement('div');
        holder.innerHTML = html || '';

        var images = { total: 0, missingAlt: 0, alts: [] };
        holder.querySelectorAll('img').forEach(function (img) {
            images.total++;
            var alt = (img.getAttribute('alt') || '').trim();
            if (!alt) { images.missingAlt++; } else { images.alts.push(alt); }
        });

        var links = { internal: 0, outbound: 0 };
        holder.querySelectorAll('a[href]').forEach(function (a) {
            var href = a.getAttribute('href') || '';
            if (/^#|^mailto:|^tel:/i.test(href)) { return; }
            if (/^https?:\/\//i.test(href) && !/fairtaxint\.com/i.test(href)) { links.outbound++; }
            else { links.internal++; }
        });

        var parsed = blocks(html);
        var paragraphs = parsed.filter(function (b) { return b.tag === 'p' || b.tag === 'li' || b.tag === 'blockquote'; });
        var headings = parsed.filter(function (b) { return /^h[1-4]$/.test(b.tag); });

        // Words between one subheading and the next, which is what Yoast
        // means by subheading distribution.
        var sections = [], running = 0;
        parsed.forEach(function (b) {
            if (/^h[1-4]$/.test(b.tag)) { sections.push({ words: running }); running = 0; }
            else { running += wordsIn(b.text).length; }
        });
        sections.push({ words: running });

        var text = parsed.map(function (b) { return b.text; }).join(' ');

        return {
            body: {
                text: text,
                words: wordsIn(text),
                sentences: sentencesIn(parsed.filter(function (b) { return b.tag !== 'li'; })
                    .map(function (b) { return b.text; }).join(' ')) || [],
                paragraphs: paragraphs,
                headings: headings,
                sections: sections
            },
            images: images,
            links: links
        };
    }

    function score(checks) {
        var counted = checks.filter(function (c) { return c.status !== OFF; });
        if (!counted.length) { return null; }

        var earned = counted.reduce(function (n, c) { return n + POINTS[c.status]; }, 0);

        return Math.round(earned / (counted.length * 9) * 100);
    }

    global.BlogSeo = {
        analyse: function (input) {
            var text = readBody(input.html);
            var seo = seoChecks(input, text);
            var read = readabilityChecks(text);

            // Without a keyphrase the SEO half is only the snippet and link
            // checks, so a score would flatter the post. Yoast shows nothing
            // there either until a keyphrase is entered.
            var hasKeyphrase = !!(input.keyphrase || '').trim();

            return {
                seo:         { score: hasKeyphrase ? score(seo) : null, checks: seo },
                readability: { score: score(read), checks: read },
                words:       text.body.words.length,
                titlePixels: pixels(input.metaTitle || input.title || '', '700 20px Arial'),
                readMinutes: Math.max(1, Math.round(text.body.words.length / 220))
            };
        },
        band: function (value) {
            if (value === null || value === undefined) { return OFF; }

            return value >= 70 ? GOOD : value >= 40 ? OK : BAD;
        }
    };
})(window);
