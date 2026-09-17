<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pharmacy onboarding</title>
    <link rel="stylesheet" href="{{ asset('assets/backend/css/style.css') }}">
    <style>
        :root { --ink:#1d2b36; --muted:#6d7d8a; --teal:#1d8e9a; --mint:#e8f5f3; --border:#dfe9f2; --soft:#f3f7f9; }
        body { margin:0; background:#f5f8fa; color:var(--ink); }
        .onboarding { max-width:1080px; margin:40px auto; padding:0 20px; }
        .brand { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
        .brand img { width:48px; height:48px; object-fit:contain; }
        .brand h1 { margin:0; font-size:26px; }
        .brand p { margin:4px 0 0; color:var(--muted); }
        .stepper { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:22px; }
        .step { padding:14px; border:1px solid var(--border); border-radius:12px; background:#fff; color:var(--muted); }
        .step strong { display:block; color:var(--ink); margin-bottom:4px; }
        .step.active { border-color:#8ad9d4; background:var(--mint); color:var(--teal); }
        .step.active strong { color:var(--teal); }
        .panel { background:#fff; border:1px solid var(--border); border-radius:18px; box-shadow:0 8px 22px rgba(15,23,42,.04); padding:26px; }
        .panel h2 { margin:0 0 6px; }
        .panel > p { color:var(--muted); margin-top:0; }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .full { grid-column:1/-1; }
        label { display:block; margin-bottom:7px; color:#5a6d7a; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        input, select, textarea { width:100%; box-sizing:border-box; min-height:42px; border:1px solid #d7e3eb; border-radius:10px; padding:10px 12px; background:#f9fbfc; }
        textarea { min-height:84px; resize:vertical; }
        .branch, .staff { border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:14px; background:var(--soft); }
        .branch h3, .staff h3 { margin:0 0 14px; font-size:16px; }
        .actions { display:flex; justify-content:space-between; gap:12px; margin-top:22px; }
        .btn { border:0; border-radius:10px; padding:11px 18px; font-weight:700; cursor:pointer; text-decoration:none; }
        .btn-primary { background:linear-gradient(135deg,#3ec2b7,var(--teal)); color:#fff; }
        .btn-light { background:var(--mint); color:#0d6f69; }
        .error { color:#c0392b; font-size:13px; margin:4px 0 0; }
        .summary { display:grid; gap:14px; }
        .summary section { border:1px solid var(--border); border-radius:12px; padding:16px; }
        .summary h3 { margin:0 0 8px; }
        .summary p { margin:4px 0; color:var(--muted); }
        @media (max-width:700px) { .stepper,.grid { grid-template-columns:1fr; } .full { grid-column:auto; } .onboarding { margin:22px auto; } }
    </style>
</head>
<body>
<div class="onboarding">
    <div class="brand">
        <img src="{{ asset('img/logo.png') }}" alt="Pharmacy">
        <div><h1>Set up your pharmacy</h1><p>Complete these steps to launch your workspace.</p></div>
    </div>

    <div class="stepper">
        @foreach(['Pharmacy Registration','Branch Setup','Staff Accounts','Review & Launch'] as $index => $label)
            <div class="step {{ $step === $index + 1 ? 'active' : '' }}">
                <strong>{{ $index + 1 }}. {{ $label }}</strong>
                <span>{{ $index + 1 < $step ? 'Completed' : ($index + 1 === $step ? 'In progress' : 'Upcoming') }}</span>
            </div>
        @endforeach
    </div>

    @if ($errors->any())
        <div class="error" style="margin-bottom:16px">{{ $errors->first() }}</div>
    @endif

    @if ($step === 1)
        <form class="panel" method="POST" action="{{ route('register.step', 1) }}">
            @csrf <h2>Pharmacy Registration</h2><p>Tell us about the business and its owner.</p>
            <div class="grid">
                <div><label>Business name</label><input name="business_name" value="{{ old('business_name', data_get($data,'pharmacy.business_name')) }}" required></div>
                <div><label>Owner name</label><input name="owner_name" value="{{ old('owner_name', data_get($data,'pharmacy.owner_name')) }}" required></div>
                <div><label>Owner email</label><input type="email" name="owner_email" value="{{ old('owner_email', data_get($data,'pharmacy.owner_email')) }}" required></div>
                <div><label>Phone</label><input name="phone" value="{{ old('phone', data_get($data,'pharmacy.phone')) }}" placeholder="03XX-XXXXXXX" required></div>
                <div><label>Tax type</label><select name="tax_type" required><option value="NTN">NTN</option><option value="STRN">STRN</option></select></div>
                <div><label>NTN / STRN</label><input name="tax_id" value="{{ old('tax_id', data_get($data,'pharmacy.tax_id')) }}" placeholder="NTN: 7 digits, STRN: 13 digits" required></div>
                <div><label>Password</label><input type="password" name="password" required></div>
                <div><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
                <div class="full"><label>Address</label><textarea name="address" required>{{ old('address', data_get($data,'pharmacy.address')) }}</textarea></div>
                <div><label>City</label><input name="city" value="{{ old('city', data_get($data,'pharmacy.city')) }}" required></div>
            </div>
            <div class="actions"><span></span><button class="btn btn-primary">Continue to branches</button></div>
        </form>
    @elseif ($step === 2)
        <form class="panel" method="POST" action="{{ route('register.step', 2) }}">
            @csrf <h2>Branch Setup</h2><p>Add every location you want active at launch. More branches can be added later.</p>
            <div id="branches">
                @foreach(old('branches', data_get($data,'branches', [[]])) as $index => $branch)
                    <div class="branch"><h3>Branch {{ $index + 1 }}</h3><div class="grid">
                        <div><label>Name</label><input name="branches[{{ $index }}][name]" value="{{ $branch['name'] ?? '' }}" required></div>
                        <div><label>City</label><input name="branches[{{ $index }}][city]" value="{{ $branch['city'] ?? '' }}" required></div>
                        <div><label>Contact</label><input name="branches[{{ $index }}][contact]" value="{{ $branch['contact'] ?? '' }}" required></div>
                        <div><label>License number</label><input name="branches[{{ $index }}][license_number]" value="{{ $branch['license_number'] ?? '' }}" required></div>
                        <div class="full"><label>Address</label><textarea name="branches[{{ $index }}][address]" required>{{ $branch['address'] ?? '' }}</textarea></div>
                    </div></div>
                @endforeach
            </div>
            <button type="button" class="btn btn-light" id="add-branch">+ Add Another Branch</button>
            <div class="actions"><a class="btn btn-light" href="{{ route('register', ['step' => 1]) }}">Back</a><button class="btn btn-primary">Continue to staff</button></div>
        </form>
    @elseif ($step === 3)
        <form class="panel" method="POST" action="{{ route('register.step', 3) }}">
            @csrf <h2>Staff Account Creation</h2><p>Each branch needs one Admin. Add optional staff accounts with database-backed roles.</p>
            <div id="staff-list">
                @foreach(data_get($data,'branches',[]) as $branchIndex => $branch)
                    <div class="staff"><h3>{{ $branch['name'] }} Admin</h3><div class="grid">
                        <input type="hidden" name="staff[{{ $branchIndex }}][branch_index]" value="{{ $branchIndex }}"><input type="hidden" name="staff[{{ $branchIndex }}][role]" value="admin">
                        <div><label>Name</label><input name="staff[{{ $branchIndex }}][name]" required></div><div><label>Email</label><input type="email" name="staff[{{ $branchIndex }}][email]" required></div>
                        <div><label>CNIC</label><input name="staff[{{ $branchIndex }}][cnic]" placeholder="XXXXX-XXXXXXX-X" required></div><div><label>Phone</label><input name="staff[{{ $branchIndex }}][phone]" required></div>
                    </div></div>
                @endforeach
            </div>
            <button type="button" class="btn btn-light" id="add-staff">+ Add Optional Staff</button>
            <div class="actions"><a class="btn btn-light" href="{{ route('register', ['step' => 2]) }}">Back</a><button class="btn btn-primary">Review setup</button></div>
        </form>
    @else
        <form class="panel" method="POST" action="{{ route('register.launch') }}">
            @csrf <h2>Review & Launch</h2><p>Confirm the complete setup before activating the pharmacy.</p>
            <div class="summary"><section><h3>{{ data_get($data,'pharmacy.business_name') }}</h3><p>{{ data_get($data,'pharmacy.owner_name') }} · {{ data_get($data,'pharmacy.owner_email') }}</p><p>{{ data_get($data,'pharmacy.tax_type') }}: {{ data_get($data,'pharmacy.tax_id') }}</p><a href="{{ route('register', ['step' => 1]) }}">Edit pharmacy</a></section>
                <section><h3>Branches</h3>@foreach(data_get($data,'branches',[]) as $branch)<p><strong>{{ $branch['name'] }}</strong> · {{ $branch['city'] }} · {{ $branch['license_number'] }}</p>@endforeach<a href="{{ route('register', ['step' => 2]) }}">Edit branches</a></section>
                <section><h3>Staff</h3>@foreach(data_get($data,'staff',[]) as $staff)<p><strong>{{ $staff['name'] }}</strong> · {{ $staff['role'] }} · {{ $staff['email'] }}</p>@endforeach<a href="{{ route('register', ['step' => 3]) }}">Edit staff</a></section>
            </div>
            <label style="margin-top:18px"><input type="checkbox" name="confirm" value="1" required> I confirm these details and want to launch the pharmacy.</label>
            <div class="actions"><a class="btn btn-light" href="{{ route('register') }}">Back</a><button class="btn btn-primary">Launch pharmacy</button></div>
        </form>
    @endif
</div>
<script>
(function () {
    var branchCount = {{ count(data_get($data, 'branches', [[]])) }};
    var staffCount = {{ count(data_get($data, 'branches', [])) }};
    var roles = @json($roles);
    var branches = @json(data_get($data, 'branches', []));
    var branchButton = document.getElementById('add-branch');
    var staffButton = document.getElementById('add-staff');
    if (branchButton) branchButton.onclick = function () {
        var i = branchCount++;
        document.getElementById('branches').insertAdjacentHTML('beforeend', '<div class="branch"><h3>Branch '+(i+1)+'</h3><div class="grid"><div><label>Name</label><input name="branches['+i+'][name]" required></div><div><label>City</label><input name="branches['+i+'][city]" required></div><div><label>Contact</label><input name="branches['+i+'][contact]" required></div><div><label>License number</label><input name="branches['+i+'][license_number]" required></div><div class="full"><label>Address</label><textarea name="branches['+i+'][address]" required></textarea></div></div></div>');
    };
    if (staffButton) staffButton.onclick = function () {
        var i = staffCount++, options = roles.map(function (role) { return '<option>'+role+'</option>'; }).join('');
        document.getElementById('staff-list').insertAdjacentHTML('beforeend', '<div class="staff"><h3>Optional Staff</h3><div class="grid"><div><label>Name</label><input name="staff['+i+'][name]" required></div><div><label>Email</label><input type="email" name="staff['+i+'][email]" required></div><div><label>CNIC</label><input name="staff['+i+'][cnic]" required></div><div><label>Phone</label><input name="staff['+i+'][phone]" required></div><div><label>Role</label><select name="staff['+i+'][role]">'+options+'</select></div><div><label>Branch</label><select name="staff['+i+'][branch_index]">'+branches.map(function (branch, index) { return '<option value="'+index+'">'+branch.name+'</option>'; }).join('')+'</select></div></div></div>');
    };
}());
</script>
</body>
</html>
