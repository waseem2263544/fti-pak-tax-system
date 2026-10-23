<?php

namespace Tests\Feature;

use App\Models\FirmRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'firm_name' => 'Unique Minerals & Co',
            'principal_place' => 'Office No. 1, Near Al-Mansoor Rest House, Maira Syedan Road, Kakul, Abbottabad',
            'other_places' => '',
            'business_description' => 'Exploration, extraction and supply of minerals',
            'deed_date' => '2026-10-21',
            'commencement_date' => '2026-10-21',
            'duration' => 'AT WILL',
            'stamp_top_cm' => '11.25',
            'managing_partner' => '0',
            'managing_powers' => "To manage the firm.\nTo represent the firm before FBR.",
            'partners' => [
                ['name' => 'Muhammad Saqib Bokhari', 'relation' => 'S/O', 'father_name' => 'Syed Muhammad Zakria', 'cnic' => '34104-4000468-5',
                 'address' => 'House No L-5, Abbottabad', 'cell' => '03165515047', 'capital_share' => '80', 'profit_share' => '80'],
                ['name' => 'Muhammad Attique', 'relation' => 'S/O', 'father_name' => 'Abdul Khalil Khan', 'cnic' => '17301-3297233-1',
                 'address' => 'Warsak Road, Peshawar', 'cell' => '03339105629', 'capital_share' => '20', 'profit_share' => '20'],
            ],
            'witnesses' => [
                ['name' => 'Nazar Hussain', 'relation' => 'S/O', 'father_name' => 'Aurang Zeb Khan', 'cnic' => '13101-7136401-5', 'address' => 'Kakul', 'cell' => ''],
                ['name' => 'Nawazish Ali', 'relation' => 'S/O', 'father_name' => 'Syed Niaz Ali Shah', 'cnic' => '', 'address' => '', 'cell' => ''],
            ],
        ], $override);
    }

    public function test_store_then_download_each_document(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('firm-registrations.create'))->assertOk()->assertSee('Partner No.', false);

        $res = $this->post(route('firm-registrations.store'), $this->payload());
        $firm = FirmRegistration::firstOrFail();
        $res->assertRedirect(route('firm-registrations.show', $firm));
        $this->assertSame('Muhammad Attique', $firm->partners[1]['name']);
        $this->assertSame(0, $firm->managing_partner);

        $this->get(route('firm-registrations.show', $firm))->assertOk()->assertSee('Download all three');
        $this->get(route('firm-registrations.index'))->assertOk()->assertSee('Unique Minerals');
        $this->get(route('firm-registrations.edit', $firm))->assertOk()->assertSee('Syed Muhammad Zakria');
        // "Copy to new firm" pre-fills partners but not the firm name
        $this->get(route('firm-registrations.create', ['from' => $firm->id]))->assertOk()
            ->assertSee('Abdul Khalil Khan')->assertDontSee('value="Unique Minerals', false);

        $this->put(route('firm-registrations.update', $firm), $this->payload(['firm_name' => 'Dashan Minerals']))
            ->assertRedirect(route('firm-registrations.show', $firm));
        $firm->refresh();
        $this->put(route('firm-registrations.update', $firm), $this->payload())->assertRedirect();
        $firm->refresh();

        foreach (['form-a' => 'Form A', 'affidavit' => 'Affidavit', 'deed' => 'Partnership Deed'] as $doc => $label) {
            $r = $this->get(route('firm-registrations.download', [$firm, $doc]));
            $r->assertOk();
            $this->assertStringContainsString($label . ' - Unique Minerals & Co.docx', $r->headers->get('content-disposition'));
            $xml = $this->docXml($r->baseResponse->getFile()->getPathname());
            $this->assertStringContainsString('w:h="20160"', $xml, "$doc should be Legal size");   // 14 in
            $this->assertStringContainsString('Muhammad Saqib Bokhari', $xml);
            $this->assertStringContainsString('&amp;', $xml);   // "&" in the firm name is escaped, file stays valid
            if ($doc === 'form-a') {
                $this->assertDoesNotMatchRegularExpression('/w:top="637\d"/', $xml);   // Form A is plain paper
            } else {
                $this->assertMatchesRegularExpression('/w:top="637\d"/', $xml, "$doc must start 11.25 cm down for stamp paper");
            }
        }

        $zip = $this->get(route('firm-registrations.download', [$firm, 'all']));
        $zip->assertOk();
        $z = new \ZipArchive();
        $z->open($zip->baseResponse->getFile()->getPathname());
        $this->assertSame(3, $z->numFiles);
    }

    public function test_shares_must_total_100(): void
    {
        $this->actingAs(User::factory()->create());
        $bad = $this->payload(['partners' => [1 => ['capital_share' => '10']]]);
        $this->post(route('firm-registrations.store'), $bad)->assertSessionHasErrors('partners');
        $this->assertSame(0, FirmRegistration::count());
    }

    public function test_cnic_format_is_checked(): void
    {
        $this->actingAs(User::factory()->create());
        $bad = $this->payload(['partners' => [0 => ['cnic' => '3410440004685']]]);
        $this->post(route('firm-registrations.store'), $bad)->assertSessionHasErrors('partners.0.cnic');
    }

    public function test_deed_without_managing_partner_omits_the_clause(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('firm-registrations.store'), $this->payload(['managing_partner' => '']));
        $firm = FirmRegistration::firstOrFail();
        $this->assertNull($firm->managing_partner);

        $r = $this->get(route('firm-registrations.download', [$firm, 'deed']));
        $xml = $this->docXml($r->baseResponse->getFile()->getPathname());
        $this->assertStringNotContainsString('MANAGING PARTNER', $xml);
        $this->assertStringContainsString('decided by the partners', $xml);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('firm-registrations.index'))->assertRedirect('/login');
    }

    private function docXml(string $path): string
    {
        $z = new \ZipArchive();
        $z->open($path);
        return $z->getFromName('word/document.xml');
    }
}
