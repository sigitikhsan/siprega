@extends('layouts.app')

@section('title', 'Ubah Password')
@section('page-title', 'Ubah Password')
@section('page-subtitle', 'Perbarui keamanan akun administrator')

@section('content')
    <style>
        .password-wrap { position: relative; }
        .password-wrap .form-control { padding-right: 5rem; }
        .password-visibility { position: absolute; top: 50%; right: .65rem; padding: .2rem .4rem; color: #5f6470; background: transparent; border: 0; font-size: .8rem; transform: translateY(-50%); }
        .password-visibility:hover { color: #1764dc; }
    </style>

    <div class="card rounded-4 mx-auto" style="max-width: 760px">
        <div class="card-body p-4 p-md-5">
            <div class="alert alert-light border mb-4">Password baru harus memiliki minimal delapan karakter.</div>
            <form method="POST" action="{{ route('admin.profile.password.update') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    @foreach ([
                        ['current_password', 'Password saat ini', 'current-password'],
                        ['password', 'Password baru', 'new-password'],
                        ['password_confirmation', 'Konfirmasi password baru', 'new-password'],
                    ] as [$field, $label, $autocomplete])
                        <div class="{{ $field === 'current_password' ? 'col-12' : 'col-md-6' }}">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <div class="password-wrap">
                                <input class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" type="password" name="{{ $field }}" autocomplete="{{ $autocomplete }}" required>
                                <button class="password-visibility" type="button" data-password-target="{{ $field }}" aria-label="Tampilkan {{ strtolower($label) }}" aria-pressed="false">Lihat</button>
                            </div>
                            @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-light" href="{{ route('admin.profile.edit') }}">Batal</a>
                    <button class="btn btn-primary" type="submit">Perbarui Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-password-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordTarget);
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.textContent = visible ? 'Lihat' : 'Sembunyikan';
                button.setAttribute('aria-pressed', visible ? 'false' : 'true');
            });
        });
    </script>
@endsection
