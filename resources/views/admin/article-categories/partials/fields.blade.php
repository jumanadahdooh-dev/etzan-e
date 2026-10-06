{{--
    Shared fields for the article category create / edit forms.
    $values     — the category being edited, or null to read old() input (create / failed validation).
    $showErrors — whether this form is the one that failed validation.
--}}
@php
    $fieldValue = fn (string $key, $default = '') => $values ? $values->{$key} : old($key, $default);
    $fieldError = fn (string $key) => $showErrors && $errors->has($key);
    $isActive = $values ? $values->is_active : old('is_active', '1') == '1';
@endphp

<div class="admin-specialty-field">
    <label>اسم التصنيف</label>
    <input type="text"
           name="name"
           value="{{ $fieldValue('name') }}"
           placeholder="مثال: تغذية علاجية"
           class="{{ $fieldError('name') ? 'is-invalid' : '' }}">

    @if ($fieldError('name'))
        <small class="admin-field-error">{{ $errors->first('name') }}</small>
    @endif
</div>

<div class="admin-specialty-field">
    <label>أيقونة (Font Awesome)</label>
    <input type="text"
           name="icon"
           value="{{ $fieldValue('icon') }}"
           placeholder="fa-regular fa-folder"
           dir="ltr"
           class="{{ $fieldError('icon') ? 'is-invalid' : '' }}">

    @if ($fieldError('icon'))
        <small class="admin-field-error">{{ $errors->first('icon') }}</small>
    @endif
</div>

<div class="admin-specialty-field">
    <label>ترتيب الظهور</label>
    <input type="number"
           name="sort_order"
           value="{{ $fieldValue('sort_order') }}"
           min="0"
           placeholder="مثال: 1"
           class="{{ $fieldError('sort_order') ? 'is-invalid' : '' }}">

    @if ($fieldError('sort_order'))
        <small class="admin-field-error">{{ $errors->first('sort_order') }}</small>
    @endif
</div>

<label class="admin-specialty-switch">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked($isActive)>
    <span></span>
    <strong>تصنيف فعّال</strong>
</label>
