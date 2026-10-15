<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1e293b; line-height: 1.6; max-width: 560px; margin: 0 auto; padding: 24px;">
    <div style="background:#143E62;color:#fff;padding:12px 16px;border-radius:8px 8px 0 0;font-weight:600;">
        <img src="{{ config('app.url') }}/bcc.svg" alt="" style="height:32px;width:auto;vertical-align:middle;margin-right:8px;background:#fff;border-radius:4px;padding:2px;">
        {{ config('city.name') }}
    </div>
    <div style="border:1px solid #e2e8f0;border-top:0;padding:16px;border-radius:0 0 8px 8px;">
        <p style="white-space:pre-line;">{{ $bodyText }}</p>
        <p style="color:#64748b;font-size:12px;margin-top:24px;">
            You received this because you have an account with {{ config('city.short_name') }} digital services.<br>
            Manage preferences in the portal under Settings.
        </p>
    </div>
</body>
</html>
