@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Create Campaign</h1>
  <form action="{{ route('user-campaign.store') }}"
        method="POST"
        enctype="multipart/form-data">
    @csrf

    {{-- Web --}}
    <div class="mb-3">
      <label>Website Name</label>
      <input type="text" name="web_name"
             class="form-control @error('web_name') is-invalid @enderror"
             value="{{ old('web_name') }}">
      @error('web_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Website URL</label>
      <input type="url" name="web_url"
             class="form-control @error('web_url') is-invalid @enderror"
             value="{{ old('web_url') }}">
      @error('web_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Keywords</label>
      <input type="text" name="web_keyword"
             class="form-control @error('web_keyword') is-invalid @enderror"
             value="{{ old('web_keyword') }}">
      @error('web_keyword') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>About</label>
      <textarea name="web_about"
                class="form-control @error('web_about') is-invalid @enderror"
                rows="3">{{ old('web_about') }}</textarea>
      @error('web_about') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Target</label>
      <select name="web_target" id="web_target"
              class="form-control @error('web_target') is-invalid @enderror">
        <option value="">— Select —</option>
        <option value="worldwide" {{ old('web_target')=='worldwide'?'selected':'' }}>
          Worldwide
        </option>
        <option value="specific_country" {{ old('web_target')=='specific_country'?'selected':'' }}>
          Specific Country
        </option>
      </select>
      @error('web_target') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3" id="countryDiv" style="display:none">
      <label>Country</label>
      <select name="country_name"
              class="form-control @error('country_name') is-invalid @enderror">
        <option value="">— Select —</option>
        @foreach($countries as $c)
          <option value="{{ $c->name }}"
            {{ old('country_name')==$c->name?'selected':'' }}>
            {{ $c->name }}
          </option>
        @endforeach
      </select>
      @error('country_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <hr>

    {{-- Company --}}
    <div class="mb-3">
      <label>Company Name</label>
      <input type="text" name="company_name"
             class="form-control @error('company_name') is-invalid @enderror"
             value="{{ old('company_name') }}">
      @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Logo</label>
      <input type="file" name="company_logo"
             class="form-control @error('company_logo') is-invalid @enderror">
      @error('company_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="company_email_address"
             class="form-control @error('company_email_address') is-invalid @enderror"
             value="{{ old('company_email_address') }}">
      @error('company_email_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Address</label>
      <textarea name="company_address"
                class="form-control @error('company_address') is-invalid @enderror"
                rows="2">{{ old('company_address') }}</textarea>
      @error('company_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Phone</label>
      <input type="text" name="company_number"
             class="form-control @error('company_number') is-invalid @enderror"
             value="{{ old('company_number') }}">
      @error('company_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Country</label>
      <select id="company_country" name="company_country"
              class="form-control @error('company_country') is-invalid @enderror">
        <option value="">— Select —</option>
        @foreach($countries as $c)
          <option value="{{ $c->id }}" {{ old('company_country')==$c->id?'selected':'' }}>
            {{ $c->name }}
          </option>
        @endforeach
      </select>
      @error('company_country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>State</label>
      <select id="company_state" name="company_state"
              class="form-control @error('company_state') is-invalid @enderror">
        <option value="">— Select —</option>
        @foreach($states as $s)
          <option value="{{ $s->id }}" data-country="{{ $s->country_id }}"
            {{ old('company_state')==$s->id?'selected':'' }}>
            {{ $s->name }}
          </option>
        @endforeach
      </select>
      @error('company_state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>City</label>
      <select id="company_city" name="company_city"
              class="form-control @error('company_city') is-invalid @enderror">
        <option value="">— Select —</option>
        @foreach($cities as $ct)
          <option value="{{ $ct->id }}" data-state="{{ $ct->state_id }}"
            {{ old('company_city')==$ct->id?'selected':'' }}>
            {{ $ct->name }}
          </option>
        @endforeach
      </select>
      @error('company_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <hr>

    {{-- Gmail --}}
    <div class="mb-3">
      <label>Gmail</label>
      <input type="email" name="gmail"
             class="form-control @error('gmail') is-invalid @enderror"
             value="{{ old('gmail') }}">
      @error('gmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="text" name="password"
             class="form-control @error('password') is-invalid @enderror"
             value="{{ old('password') }}">
      @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <button class="btn btn-success">Save Campaign</button>
  </form>
</div>
@endsection

@push('scripts')
<script>
  const states = @json($states);
  const cities = @json($cities);

  function filterStates(countryId) {
    $('#company_state').html(
      '<option value="">— Select —</option>' +
      states.filter(s => s.country_id == countryId)
            .map(s => `<option value="${s.id}">${s.name}</option>`)
            .join('')
    );
    filterCities(null);
  }
  function filterCities(stateId) {
    $('#company_city').html(
      '<option value="">— Select —</option>' +
      cities.filter(c => c.state_id == stateId)
            .map(c => `<option value="${c.id}">${c.name}</option>`)
            .join('')
    );
  }

  $('#company_country').on('change', e => filterStates(e.target.value));
  $('#company_state'  ).on('change', e => filterCities(e.target.value));

  // Web target toggle
  function toggleCountry() {
    $('#countryDiv').toggle($('#web_target').val() === 'specific_country');
  }
  $('#web_target').on('change', toggleCountry);
  toggleCountry();
</script>
@endpush
