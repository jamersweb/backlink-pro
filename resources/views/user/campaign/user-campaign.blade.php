@extends('layouts.app')

@section('content')
  <h3>User Campaign</h3>

  <form action="" method="">
    @csrf
    <div class="mb-3">
      <label for="website_url" class="form-label">Website Url</label>
      <input type="text" class="form-control" id="website_url" name="website_url" placeholder="Please Enter Your Website URL" required>
      </div>
    <div class="mb-3">
      <label for="website_name" class="form-label">Website Name</label>
      <input type="text" class="form-control" id="website_name" name="website_name" placeholder="Please Enter Your website Name" required>
      </div>
    <div class="mb-3">
      <label for="website_about" class="form-label">Website About Us</label>
      <input type="text" class="form-control" id="website_about" name="website_about" placeholder="Please Enter Your Website About Us" required>
      </div>
    <div class="mb-3">
      <label for="website_keyword" class="form-label">Website Keyword</label>
      <input type="text" class="form-control" id="website_keyword" name="website_keyword" placeholder="Please Enter Your Website Keyword" required>
      </div>
   <div class="mb-3">
      <label for="website_target" class="form-label">
        Rank Your Website Worldwide Or Target a Specific Country?
      </label>
      <select id="website_target" class="form-select" required>
        <option value="">-- Select Target --</option>
        <option value="worldwide">Worldwide</option>
        <option value="specific_country">Specific Country</option>
      </select>
    </div>

    <div id="specific-country-block" style="display:none; margin-top:1rem;">
      <h3>Select Country</h3>
      <img id="country-flag" src="" width="32"
           style="vertical-align:middle; margin-right:8px;" alt="Flag">

      <select id="country-select"
              style="width:250px; background-color:black; color:white;">
        <option value="">-- Loading Countries... --</option>
      </select>
    </div>
      </form>
      
 
@endsection
@push('scripts')
<script>
  $(function(){
    const $target = $('#website_target'),
          $block  = $('#specific-country-block'),
          $country= $('#country-select'),
          $flag   = $('#country-flag');

    // on change of Worldwide/Specific
    $target.on('change', function(){
      if (this.value === 'specific_country') {
        $block.slideDown();
        if ($country.children().length <= 1) {
          // fetch countries only once
          fetchCountries();
        }
      } else {
        $block.slideUp();
        $country.empty().append('<option value="">-- Select Country --</option>');
        $flag.attr('src','');
      }
    });

    function fetchCountries() {
      $country.prop('disabled', true)
              .empty()
              .append('<option value="">-- Loading... --</option>');

      $.ajax({
        url: 'https://restcountries.com/v3.1/all',
        method: 'GET',
        success(data) {
          $country.empty().append('<option value="">-- Select Country --</option>');
          // sort by name
          data.sort((a,b) => a.name.common.localeCompare(b.name.common));
          data.forEach(c => {
            const name = c.name.common,
                  flag = c.flags?.png || '';
            $('<option>', {
              value: name,
              'data-flag': flag,
              text: name
            }).appendTo($country);
          });
          $country.prop('disabled', false);
          initSelect2();  // agar Select2 chahiye
        },
        error() {
          $country.empty()
                  .append('<option value="">-- Error loading countries --</option>');
        }
      });
    }

    function initSelect2() {
      $country.select2({
        placeholder: "-- Select Country --",
        templateResult: formatCountry,
        templateSelection: formatCountry,
        escapeMarkup: m => m
      }).on('change', function(){
        const url = $(this).find(':selected').data('flag') || '';
        $flag.attr('src', url);
      });
    }

    function formatCountry(item) {
      if (!item.id) return item.text;
      const url = $country.find(`option[value="${item.id}"]`).data('flag');
      return `<span style="display:flex; align-items:center;">
                <img src="${url}" width="20" style="margin-right:8px;"/>
                ${item.text}
              </span>`;
    }
  });
</script>
@endpush
