@php $r = Route::currentRouteName(); @endphp
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link @if(in_array($r, ['social.index', 'social.show'])) active @endif" href="{{ route('social.index') }}">
            <i class="bi bi-kanban me-1"></i> Review queue
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if($r === 'social.create') active @endif" href="{{ route('social.create') }}">
            <i class="bi bi-plus-lg me-1"></i> New post
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if($r === 'social.ideas') active @endif" href="{{ route('social.ideas') }}">
            <i class="bi bi-lightbulb me-1"></i> Ideas
        </a>
    </li>
    @if(Auth::user()->hasRole('admin'))
    <li class="nav-item ms-auto">
        <a class="nav-link @if($r === 'social.settings') active @endif" href="{{ route('social.settings') }}">
            <i class="bi bi-sliders me-1"></i> Settings
        </a>
    </li>
    @endif
</ul>
