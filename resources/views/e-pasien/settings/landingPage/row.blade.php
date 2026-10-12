<article class="landing-editor-row" data-editor-row>
    <div class="landing-editor-row-head">
        <strong>Item <span data-row-number>{{ is_numeric($index) ? $index + 1 : '' }}</span></strong>
        <div>
            <button type="button" data-row-up aria-label="Pindahkan item ke atas" title="Pindahkan ke atas"><i class="bi bi-arrow-up"></i></button>
            <button type="button" data-row-down aria-label="Pindahkan item ke bawah" title="Pindahkan ke bawah"><i class="bi bi-arrow-down"></i></button>
            <button type="button" data-row-remove class="is-danger" aria-label="Hapus item" title="Hapus item"><i class="bi bi-trash"></i></button>
        </div>
    </div>
    <div class="landing-editor-fields">
        @foreach ($collection['fields'] as $fieldKey => $field)
            @include('e-pasien.settings.landingPage.field', [
                'name' => "content[{$key}][{$index}][{$fieldKey}]",
                'id' => "editor-{$key}-{$index}-{$fieldKey}",
                'errorKey' => "content.{$key}.{$index}.{$fieldKey}",
                'value' => $row[$fieldKey] ?? $field['default'],
            ])
        @endforeach
    </div>
</article>
