@extends('layouts.app')

@section('content')
  <h3>Country / State / City Selector</h3>
  <img id="country-flag" src="" width="32" style="vertical-align:middle; margin-right:8px;" alt="Flag">

  <select id="country-select" style="width:250px; background-color:black; color:white;">
    <option value="">-- Select Country --</option>
    @foreach($countries as $c)
      <option
        value="{{ $c['name']['common'] }}"
        data-flag="{{ $c['flags']['png'] ?? '' }}"
      >{{ $c['name']['common'] }}</option>
    @endforeach
  </select>

  <select id="state-select" disabled style="color:black; width:250px;">
    <option value="">-- Select State --</option>
  </select>

  <select id="city-select" disabled  style="color:black; width:250px;>
    <option value="">-- Select City --</option>
  </select>
@endsection

@push('scripts')
<script>
  $(function(){
    const $country = $('#country-select'),
          $state   = $('#state-select'),
          $city    = $('#city-select'),
          $flag    = $('#country-flag');

    // initialize Select2
    $country.select2({
      placeholder: "-- Select Country --",
      templateResult: formatCountry,
      templateSelection: formatCountry,
      escapeMarkup: m => m
    });

    function formatCountry(item) {
      if (!item.id) return item.text;
      let url = $country.find(`option[value="${item.id}"]`).data('flag');
      return `<span style="display:flex; align-items:center;">
                <img src="${url}" width="20" style="margin-right:8px;"/>
                ${item.text}
              </span>`;
    }

    // on country change…
    $country.on('change', function(){
      let name = $(this).val();
      $flag.attr('src', $(this).find(':selected').data('flag')||'');
      $state.html('<option>-- Select State --</option>').prop('disabled', true);
      $city.html('<option>-- Select City --</option>').prop('disabled', true);
      if (!name) return;

      fetch("{{ route('admin.countries.states') }}", { 
        method:'POST', 
        headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' },
        body: JSON.stringify({ country: name })
      })
      .then(r=>r.json())
      .then(states=>{
        states.forEach(s=> $state.append(new Option(s.name, s.name)) );
        $state.prop('disabled', false);
      });
    });

    // on state change…
    $state.on('change', function(){
      let state = $(this).val(),
          country = $country.val();
      $city.html('<option>-- Select City --</option>').prop('disabled', true);
      if (!state) return;

      fetch("{{ route('admin.countries.cities') }}", { 
        method:'POST', 
        headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' },
        body: JSON.stringify({ country, state })
      })
      .then(r=>r.json())
      .then(cities=>{
        cities.forEach(n=> $city.append(new Option(n, n)) );
        $city.prop('disabled', false);
      });
    });
  });
</script>
@endpush
