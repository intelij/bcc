<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Bill;
use App\Models\MunicipalAccount;
use App\Models\Payment;
use App\Models\Property;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Global administrative search across all major entities. */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->q);
        $like = '%'.$q.'%';
        $results = collect();

        if (strlen($q) >= 2) {
            $results = collect([
                'Users' => User::where('name', 'like', $like)->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)->limit(10)->get()
                    ->map(fn ($u) => [$u->name.' — '.$u->email, route('admin.users.show', $u)]),
                'Properties' => Property::where('address_line', 'like', $like)->orWhere('reference', 'like', $like)
                    ->orWhere('suburb', 'like', $like)->limit(10)->get()
                    ->map(fn ($p) => [$p->fullAddress().' ('.$p->reference.')', route('admin.properties.show', $p)]),
                'Accounts' => MunicipalAccount::where('account_number', 'like', $like)
                    ->orWhere('holder_name', 'like', $like)->limit(10)->get()
                    ->map(fn ($a) => [$a->account_number.' — '.$a->holder_name, route('admin.accounts.show', $a)]),
                'Bills' => Bill::where('invoice_number', 'like', $like)->limit(10)->get()
                    ->map(fn ($b) => [$b->invoice_number.' — '.money($b->total_cents), route('admin.billing.show', $b)]),
                'Payments' => Payment::where('uuid', 'like', $like)->orWhere('reference', 'like', $like)
                    ->orWhere('provider_reference', 'like', $like)->limit(10)->get()
                    ->map(fn ($p) => [$p->uuid.' — '.money($p->amount_cents), route('admin.payments.show', $p)]),
                'Service requests' => ServiceRequest::where('ticket_number', 'like', $like)
                    ->orWhere('description', 'like', $like)->limit(10)->get()
                    ->map(fn ($r) => [$r->ticket_number.' — '.$r->categoryLabel(), route('admin.requests.show', $r)]),
                'Announcements' => Announcement::where('title', 'like', $like)->limit(10)->get()
                    ->map(fn ($a) => [$a->title, route('admin.announcements.edit', $a)]),
            ]);
        }

        return view('admin.search', compact('q', 'results'));
    }
}
