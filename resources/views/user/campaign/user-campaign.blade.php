@extends('layouts.app')

@section('content')
  <div class="container">
    <h3>Create User Campaign</h3>

    <form 
      action="{{ route('user-campaign.store') }}" 
      method="POST" 
      enctype="multipart/form-data"
    >
      @csrf

      {{-- Web Information --}}
      <h5>Web Information</h5>

      <div class="mb-3">
        <label for="web_name" class="form-label">Website Name</label>
        <input
          type="text"
          class="form-control"
          id="web_name"
          name="web_name"
          value="{{ old('web_name') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="web_url" class="form-label">Website URL</label>
        <input
          type="url"
          class="form-control"
          id="web_url"
          name="web_url"
          value="{{ old('web_url') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="web_keyword" class="form-label">Website Keywords</label>
        <input
          type="text"
          class="form-control"
          id="web_keyword"
          name="web_keyword"
          value="{{ old('web_keyword') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="web_about" class="form-label">Website About</label>
        <textarea
          class="form-control"
          id="web_about"
          name="web_about"
          rows="3"
          required
        >{{ old('web_about') }}</textarea>
      </div>

      <div class="mb-3">
        <label for="web_target" class="form-label">
          Rank Worldwide or Specific Country?
        </label>
        <select
          id="web_target"
          name="web_target"
          class="form-select"
          required
        >
          <option value="">-- Select Target --</option>
          <option value="worldwide" {{ old('web_target')=='worldwide' ? 'selected' : '' }}>
            Worldwide
          </option>
          <option value="specific_country" {{ old('web_target')=='specific_country' ? 'selected' : '' }}>
            Specific Country
          </option>
        </select>
      </div>

      <div id="countryDiv" class="mb-3" style="display: none;">
        <label for="country_name" class="form-label">Select Country</label>
        <select
          id="country_name"
          name="country_name"
          class="form-select"
        >
          <option value="">-- Select Country --</option>
          @foreach($countries as $c)
            <option value="{{ $c->name }}"
              {{ old('country_name') == $c->name ? 'selected' : '' }}
            >
              {{ $c->name }}
            </option>
          @endforeach
        </select>
      </div>

      <hr>

      {{-- Company Information --}}
      <h5>Company Information</h5>

      <div class="mb-3">
        <label for="company_name" class="form-label">Company Name</label>
        <input
          type="text"
          class="form-control"
          id="company_name"
          name="company_name"
          value="{{ old('company_name') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="company_logo" class="form-label">Company Logo</label>
        <input
          type="file"
          class="form-control"
          id="company_logo"
          name="company_logo"
          accept="image/*"
          required
        >
      </div>

      <div class="mb-3">
        <label for="company_email_address" class="form-label">Company Email</label>
        <input
          type="email"
          class="form-control"
          id="company_email_address"
          name="company_email_address"
          value="{{ old('company_email_address') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="company_address" class="form-label">Company Address</label>
        <textarea
          class="form-control"
          id="company_address"
          name="company_address"
          rows="2"
          required
        >{{ old('company_address') }}</textarea>
      </div>

      <div class="mb-3">
        <label for="company_number" class="form-label">Company Phone</label>
        <input
          type="text"
          class="form-control"
          id="company_number"
          name="company_number"
          value="{{ old('company_number') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="company_country" class="form-label">Company Country</label>
        <input
          type="text"
          class="form-control"
          id="company_country"
          name="company_country"
          value="{{ old('company_country') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="company_city" class="form-label">Company City</label>
        <input
          type="text"
          class="form-control"
          id="company_city"
          name="company_city"
          value="{{ old('company_city') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="company_state" class="form-label">Company State</label>
        <input
          type="text"
          class="form-control"
          id="company_state"
          name="company_state"
          value="{{ old('company_state') }}"
          required
        >
      </div>

      <hr>

      {{-- User Gmail --}}
      <h5>User Gmail</h5>

      <div class="mb-3">
        <label for="gmail" class="form-label">Gmail Address</label>
        <input
          type="email"
          class="form-control"
          id="gmail"
          name="gmail"
          value="{{ old('gmail') }}"
          required
        >
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password (plain text)</label>
        <input
          type="text"
          class="form-control"
          id="password"
          name="password"
          value="{{ old('password') }}"
          required
        >
      </div>

      <button type="submit" class="btn btn-success">Save Campaign</button>
    </form>
  </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(function(){
    // show country selector only if specific_country chosen
    function toggleCountry(){
      if ($('#web_target').val() === 'specific_country') {
        $('#countryDiv').slideDown();
      } else {
        $('#countryDiv').slideUp()
                        .find('select').val('');
      }
    }
    $('#web_target').on('change', toggleCountry);
    // on page load (for validation errors), check old value:
    toggleCountry();
  });
</script>
@endpush
