<div class="card">
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $action }}">@csrf @if ($method !== 'POST')@method($method)@endif
        <div class="user-form-grid">
            <div class="form-group"><label for="code">Kode outlet *</label><input class="form-control" id="code" name="code" value="{{ old('code', $outlet?->code) }}" required maxlength="50" placeholder="Contoh: OUTLET-01"></div>
            <div class="form-group"><label for="name">Nama outlet *</label><input class="form-control" id="name" name="name" value="{{ old('name', $outlet?->name) }}" required maxlength="255"></div>
            <div class="form-group"><label for="phone">Telepon</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $outlet?->phone) }}" maxlength="50"></div>
            <div class="form-group"><label for="address">Alamat</label><textarea class="form-control" id="address" name="address" rows="3" maxlength="1000">{{ old('address', $outlet?->address) }}</textarea></div>
            @if ($outlet)<div class="form-group"><label for="is_active">Status</label><select class="form-control" id="is_active" name="is_active" required><option value="1" @selected((string) old('is_active', (int) $outlet->is_active) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', (int) $outlet->is_active) === '0')>Nonaktif</option></select></div>@endif
        </div>
        <div class="form-actions"><a class="btn btn-secondary" href="{{ route('outlets.index') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan Outlet</button></div>
    </form>
</div>
