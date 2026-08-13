<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Models\WhtSection;
use Illuminate\Http\Request;

/**
 * Global FBR payment sections. Seeded by migrate-wht.php; editable by admins so
 * new heads can be added without a deploy.
 */
class WhtSectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()?->hasRole('admin'), 403, 'Only administrators can manage sections.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = WhtSection::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('section', 'like', "%{$search}%")
                ->orWhere('payment_nature', 'like', "%{$search}%")
                ->orWhere('payment_section', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        if ($request->filled('applies_to')) {
            $query->where('applies_to', $request->applies_to);
        }

        $sections = $query->orderBy('code')->paginate(50)->withQueryString();

        return view('wht.sections.index', compact('sections'));
    }

    public function store(Request $request)
    {
        WhtSection::create($this->validated($request));

        return back()->with('success', 'Section added.');
    }

    public function update(Request $request, WhtSection $section)
    {
        $section->update($this->validated($request, $section));

        return back()->with('success', 'Section updated.');
    }

    public function destroy(WhtSection $section)
    {
        $section->delete();

        return back()->with('success', 'Section deleted.');
    }

    private function validated(Request $request, ?WhtSection $section = null): array
    {
        $unique = 'unique:wht_sections,code' . ($section ? ',' . $section->id : '');

        $data = $request->validate([
            'section'         => 'required|string|max:50',
            'payment_nature'  => 'required|string|max:150',
            'payment_section' => 'required|string|max:255',
            'code'            => ['required', 'string', 'max:50', $unique],
            'applies_to'      => 'required|in:purchase,salary,both',
            'is_active'       => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
