{{-- Renders a head's own attributes. $fields comes from FbrSchema, $values from
     the line's details, $name is the input-name prefix. --}}
@foreach($fields as $field)
    @php
        $key  = $field['key'];
        $val  = $values[$key] ?? '';
        $id   = $name . '_' . $key;
        $span = ($field['wide'] ?? false) ? 'col-md-6' : 'col-md-3';
    @endphp
    <div class="{{ $span }}">
        <label class="form-label" for="{{ $id }}">{{ $field['label'] }}</label>
        @if($field['type'] === 'select')
            <select name="{{ $name }}[{{ $key }}]" id="{{ $id }}" class="form-select form-select-sm">
                <option value=""></option>
                @foreach($field['options'] as $opt)
                    <option value="{{ $opt }}" @selected((string) $val === (string) $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        @elseif($field['type'] === 'date')
            <input type="date" name="{{ $name }}[{{ $key }}]" id="{{ $id }}" class="form-control form-control-sm" value="{{ $val }}">
        @elseif(in_array($field['type'], ['number', 'money']))
            <input type="number" step="{{ $field['type'] === 'money' ? '0.01' : 'any' }}"
                   name="{{ $name }}[{{ $key }}]" id="{{ $id }}"
                   class="form-control form-control-sm num" value="{{ $val }}">
        @else
            <input type="text" name="{{ $name }}[{{ $key }}]" id="{{ $id }}" class="form-control form-control-sm" value="{{ $val }}">
        @endif
    </div>
@endforeach
