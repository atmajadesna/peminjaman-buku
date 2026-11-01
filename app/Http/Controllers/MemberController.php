<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $members = Member::query()
            ->withCount(['activeLoans'])
            ->search($search)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('members.index', [
            'members' => $members,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:members,email'],
            'phone' => ['nullable', 'string', 'max:24'],
            'role' => ['required', 'in:Mahasiswa,Dosen,Staff,Umum'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar sebagai anggota.',
        ]);

        $data['code'] = $this->nextCode();
        $data['is_active'] = true;

        $member = Member::create($data);

        return redirect()
            ->route('members.index')
            ->with('status', sprintf('Anggota %s (%s) berhasil didaftarkan.', $member->name, $member->code));
    }

    private function nextCode(): string
    {
        $last = Member::query()->orderByDesc('id')->value('code');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return 'AGT-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
