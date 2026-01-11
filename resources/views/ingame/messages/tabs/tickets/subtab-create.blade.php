<div style="padding: 20px;">
    <h3 style="color: #6f9fc8; margin-bottom: 20px;">@lang('Create New Support Ticket')</h3>

    @if (session('status'))
        <div style="background: #1a472a; color: #5cb85c; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background: #4a1a1a; color: #d9534f; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('tickets.store') }}" method="POST">
        @csrf

        <div class="group bborder" style="display: block; margin-bottom: 15px;">
            <label for="subject" style="display: block; color: #6f9fc8; margin-bottom: 5px;">@lang('Subject') *</label>
            <input type="text"
                   name="subject"
                   id="subject"
                   value="{{ old('subject') }}"
                   required
                   maxlength="255"
                   style="width: 100%; padding: 8px; background: #0d1014; border: 1px solid #3a4a5a; color: #fff; border-radius: 3px;">
            @error('subject')
                <div style="color: #d9534f; font-size: 11px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div class="group bborder" style="display: block; margin-bottom: 15px;">
            <label for="body" style="display: block; color: #6f9fc8; margin-bottom: 5px;">@lang('Message') *</label>
            <textarea name="body"
                      id="body"
                      rows="8"
                      required
                      minlength="1"
                      maxlength="5000"
                      style="width: 100%; padding: 10px; background: #0d1014; border: 1px solid #3a4a5a; color: #fff; border-radius: 3px; resize: vertical;"
                      placeholder="@lang('Describe your issue or question...')">{{ old('body') }}</textarea>
            @error('body')
                <div style="color: #d9534f; font-size: 11px; margin-top: 5px;">{{ $message }}</div>
            @enderror
        </div>

        <div style="text-align: right;">
            <button type="submit" class="btn_blue" style="padding: 10px 20px;">
                @lang('Submit Ticket')
            </button>
        </div>
    </form>
</div>

<style>
    .group.bborder {
        background: rgba(0, 0, 0, 0.2);
        padding: 15px;
        border: 1px solid #3a4a5a;
        border-radius: 3px;
    }
</style>
