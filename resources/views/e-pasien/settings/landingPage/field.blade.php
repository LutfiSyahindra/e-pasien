@php
    $type = $field['type'];
    $value ??= $field['default'];
    $required = !($field['optional'] ?? false);
@endphp

@if ($type === 'hidden')
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-field="{{ $fieldKey }}">
@else
    <div class="landing-editor-field {{ $type === 'textarea' || $type === 'url' ? 'is-wide' : '' }}">
        <label for="{{ $id }}" data-field-label="{{ $fieldKey }}">{{ $field['label'] }}</label>
        @if ($type === 'select')
            <select id="{{ $id }}" name="{{ $name }}" data-field="{{ $fieldKey }}" @required($required)>
                @foreach ($field['options'] as $option => $label)
                    <option value="{{ $option }}" @selected($value === $option)>{{ $label }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="3" maxlength="{{ $field['max'] }}"
                data-field="{{ $fieldKey }}" @required($required)>{{ $value }}</textarea>
        @else
            <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
                maxlength="{{ $field['max'] }}" data-field="{{ $fieldKey }}" @required($required)>
        @endif
        @error($errorKey)
            <small class="landing-editor-error">{{ $message }}</small>
        @enderror
    </div>
@endif
