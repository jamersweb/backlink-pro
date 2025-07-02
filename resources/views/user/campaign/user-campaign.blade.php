@extends('layouts.app')

@section('content')
<div class="container">
  <h1>{{ isset($campaign) ? 'Edit Campaign' : 'Create Campaign' }}</h1>
  <form action="{{ isset($campaign) ? route('user-campaign.update', $campaign->id) : route('user-campaign.store') }}"
        method="POST"
        enctype="multipart/form-data">
    @csrf
    @if(isset($campaign))
      @method('PUT')
    @endif

    {{-- Web --}}
    <div class="mb-3">
      <label>Website Name</label>
      <input type="text" name="web_name"
             class="form-control @error('web_name') is-invalid @enderror"
             value="{{ old('web_name', $campaign->web_name ?? '') }}">
      @error('web_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Website URL</label>
      <input type="url" name="web_url"
             class="form-control @error('web_url') is-invalid @enderror"
             value="{{ old('web_url', $campaign->web_url ?? '') }}">
      @error('web_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Keywords</label>
      <input type="text" name="web_keyword"
             class="form-control @error('web_keyword') is-invalid @enderror"
             value="{{ old('web_keyword', $campaign->web_keyword ?? '') }}">
      @error('web_keyword') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>About</label>
      <textarea name="web_about"
                class="form-control @error('web_about') is-invalid @enderror"
                rows="3">{{ old('web_about', $campaign->web_about ?? '') }}</textarea>
      @error('web_about') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Target</label>
      <select name="web_target" id="web_target"
              class="form-control @error('web_target') is-invalid @enderror">
        <option value="">— Select —</option>
        <option value="worldwide" {{ old('web_target', $campaign->web_target ?? '') == 'worldwide' ? 'selected' : '' }}>
          Worldwide
        </option>
        <option value="specific_country" {{ old('web_target', $campaign->web_target ?? '') == 'specific_country' ? 'selected' : '' }}>
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
            {{ old('country_name', $campaign->country_name ?? '') == $c->name ? 'selected' : '' }}>
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
             value="{{ old('company_name', $campaign->company_name ?? '') }}">
      @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
<div class="mb-3">
  <label>Logo</label>

  @if(isset($campaign) && $campaign->company_logo)
    <div class="mb-2">
      <img src="{{ asset($campaign->company_logo) }}" width="100" alt="Logo">
    </div>
  @endif

  <div class="input-group">
    <div class="input-group-prepend">
      <button
        type="button"
        class="btn btn-secondary"
        id="triggerLogoPicker"
      >Choose File</button>
    </div>
    <input
      type="text"
      id="logoFilename"
      class="form-control"
      value="{{ isset($campaign) ? basename($campaign->company_logo) : '' }}"
      readonly
    >
  </div>
  <input
    type="file"
    id="realLogoInput"
    name="company_logo"
    style="position:absolute;left:-9999px;"
    accept="image/jpg,image/jpeg,image/png"
  >

  @error('company_logo')
    <div class="text-danger">{{ $message }}</div>
  @enderror
</div>



    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="company_email_address"
             class="form-control @error('company_email_address') is-invalid @enderror"
             value="{{ old('company_email_address', $campaign->company_email_address ?? '') }}">
      @error('company_email_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Address</label>
      <textarea name="company_address"
                class="form-control @error('company_address') is-invalid @enderror"
                rows="2">{{ old('company_address', $campaign->company_address ?? '') }}</textarea>
      @error('company_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Phone</label>
      <input type="text" name="company_number"
             class="form-control @error('company_number') is-invalid @enderror"
             value="{{ old('company_number', $campaign->company_number ?? '') }}">
      @error('company_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Country</label>
      <select id="company_country" name="company_country"
              class="form-control @error('company_country') is-invalid @enderror">
        <option value="">— Select —</option>
        @foreach($countries as $c)
          <option value="{{ $c->id }}" {{ old('company_country', $campaign->company_country ?? '') == $c->id ? 'selected' : '' }}>
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
      </select>
      @error('company_state') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>City</label>
      <select id="company_city" name="company_city"
              class="form-control @error('company_city') is-invalid @enderror">
        <option value="">— Select —</option>
      </select>
      @error('company_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <hr>

    {{-- Gmail --}}
    <div class="mb-3">
      <label>Gmail</label>
      <input type="email" name="gmail"
             class="form-control @error('gmail') is-invalid @enderror"
             value="{{ old('gmail', $campaign->gmail ?? '') }}">
      @error('gmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="text" name="password"
             class="form-control @error('password') is-invalid @enderror"
             value="{{ old('password', $campaign->password ?? '') }}">
      @error('password') <div class="invalid-feedback">{{ $message }}"></div> @enderror
    </div>

    <button class="btn btn-success">{{ isset($campaign) ? 'Update Campaign' : 'Save Campaign' }}</button>
     <a href="{{ route('user-campaign.index') }}" class="btn btn-secondary">Back to list</a>
  </form>
</div>
@endsection


@push('scripts')
<script>
$(document).ready(function() {
  const states = @json($states);
  const cities = @json($cities);

  function loadStates(countryId, selectedState) {
    let html = '<option value="">— Select —</option>';
    states.forEach(s => {
      if (s.country_id == countryId) {
        html += `<option value="${s.id}"${selectedState == s.id ? ' selected' : ''}>${s.name}</option>`;
      }
    });
    $('#company_state').html(html);
  }

  function loadCities(stateId, selectedCity) {
    let html = '<option value="">— Select —</option>';
    cities.forEach(c => {
      if (c.state_id == stateId) {
        html += `<option value="${c.id}"${selectedCity == c.id ? ' selected' : ''}>${c.name}</option>`;
      }
    });
    $('#company_city').html(html);
  }

  // When user picks a country
  $('#company_country').on('change', function() {
    const country = $(this).val();
    loadStates(country, null);
    $('#company_city').html('<option value="">— Select —</option>');
  });

  // When user picks a state
  $('#company_state').on('change', function() {
    const state = $(this).val();
    loadCities(state, null);
  });
  
  // Initialize on edit page
  @if(old('company_country', isset($campaign) ? $campaign->company_country : false))
    const initCountry = '{{ old('company_country', $campaign->company_country ?? '') }}';
    const initState   = '{{ old('company_state',   $campaign->company_state   ?? '') }}';
    const initCity    = '{{ old('company_city',    $campaign->company_city    ?? '') }}';

    // Set country, then populate states & cities in order
    $('#company_country').val(initCountry);
    loadStates(initCountry, initState);
    $('#company_state').val(initState);
    loadCities(initState, initCity);
    $('#company_city').val(initCity);
  @endif


    function toggleCountry() {
      $('#countryDiv').toggle($('#web_target').val() === 'specific_country');
    }
    $('#web_target').on('change', toggleCountry);
    toggleCountry();
  });
   $('#triggerLogoPicker').on('click', () => {
    $('#realLogoInput').click();
  });
  $('#realLogoInput').on('change', function() {
    $('#logoFilename').val(this.files[0]?.name || '');
  });

</script>
@endpush


