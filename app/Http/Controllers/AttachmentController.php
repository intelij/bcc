<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Gate;

/** Authorized download of private-disk uploads. */
class AttachmentController extends Controller
{
    public function show(Attachment $attachment)
    {
        Gate::authorize('view', $attachment);

        return $attachment->stream();
    }
}
