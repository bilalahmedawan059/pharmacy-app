<form method="POST" enctype="multipart/form-data" action="{{ route('users') }}">
    @csrf
    <div class="row form-row">
        <div class="col-12">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control add_name" placeholder="John Doe">
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control add_email">
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label>Role</label>
                <div class="form-group">
                    <select class="select2 form-select form-control add_role" name="role">
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        @if (auth()->user()->hasRole('super-admin'))
            <div class="col-12">
                <div class="form-group">
                    <label>Pharmacy</label>
                    <select class="select2 form-select form-control add_pharmacy" name="pharmacy_id" required>
                        <option value="">Select pharmacy</option>
                        @foreach ($pharmacies as $pharmacy)
                            <option value="{{ $pharmacy->id }}">{{ $pharmacy->business_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
        <div class="col-12">
           <div class="row">
               <div class="col-md-8">
                <div class="form-group">
                    <label>Picture</label>
                    <input type="file" name="avatar">
                </div>
               </div>
               <div class="col-md-4" id="avatar">
                  <img width="40" src="{{ asset('storage/users/'.auth()->user()->avatar) }}" alt="">
               </div>
           </div>
        </div>
        <div class="col-12">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
</form>
