@php
    $avatarDefault = app(\App\Services\AvatarService::class)->defaultUrl();
    $avatarCurrent = isset($avatarUser) ? $avatarUser->profile_picture_url : $avatarDefault;
    $hasPicture = isset($avatarUser) && $avatarUser->playerProfile?->avatar_path;
    $avatarEnabled = app(\App\Services\AvatarService::class)->enabled();
@endphp
<div class="avatar-editor" data-avatar-editor data-current="{{ $avatarCurrent }}" data-default="{{ $avatarDefault }}">
    <div class="avatar-preview-wrap">
        <img class="avatar-preview" src="{{ $avatarCurrent }}" width="88" height="88" alt="Profile picture preview" data-avatar-preview data-avatar data-fallback="{{ $avatarDefault }}">
        <label for="profile_picture" class="avatar-camera" aria-label="Choose a profile picture"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h4l2-3h4l2 3h4v13H4Z"/><circle cx="12" cy="13" r="4"/></svg></label>
    </div>
    <div class="field-group avatar-upload-field">
        <label for="profile_picture">Profile picture <span class="muted">(optional)</span></label>
        <input id="profile_picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="picture-help picture-error" data-avatar-input @disabled(!$avatarEnabled)>
        <p class="field-help" id="picture-help">JPG, PNG or WebP. Up to 2 MB, 12 megapixels and 4096 pixels per side. Shown with your username.</p>
        @unless ($avatarEnabled)<p class="field-help">Photo uploads are temporarily unavailable. You can continue without a picture.</p>@endunless
        <button type="button" class="avatar-clear" data-avatar-clear hidden>Clear selection</button>
        @if ($hasPicture)
            <label class="avatar-remove"><input type="checkbox" name="remove_profile_picture" value="1" data-avatar-remove> Remove current picture</label>
        @endif
        <p class="field-error" id="picture-error" data-avatar-error role="status">@error('profile_picture'){{ $message }}@enderror</p>
        @error('expected_revision')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>
