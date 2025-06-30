<!-- resources/views/locations/form.blade.php -->
<select id="country" name="country_id">
  <option value="">-- Select Country --</option>
  @foreach($countries as $c)
    <option value="{{ $c->id }}">{{ $c->name }}</option>
  @endforeach
</select>

<select id="state" name="state_id" disabled>
  <option value="">-- Select State --</option>
</select>

<select id="city" name="city_id" disabled>
  <option value="">-- Select City --</option>
</select>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('#country').change(function(){
  let countryId = $(this).val();
  if (!countryId) {
    $('#state, #city').prop('disabled', true).html('<option value="">-- Select --</option>');
    return;
  }
  $.getJSON(`/admin/locations/states/${countryId}`, function(states){
    let options = '<option value="">-- Select State --</option>';
    states.forEach(s => {
      options += `<option value="${s.id}">${s.name}</option>`;
    });
    $('#state').prop('disabled', false).html(options);
    $('#city').prop('disabled', true).html('<option value="">-- Select City --</option>');
  });
});

$('#state').change(function(){
  let stateId = $(this).val();
  if (!stateId) {
    $('#city').prop('disabled', true).html('<option value="">-- Select City --</option>');
    return;
  }
  $.getJSON(`/admin/locations/cities/${stateId}`, function(cities){
    let opts = '<option value="">-- Select City --</option>';
    cities.forEach(c => {
      opts += `<option value="${c.id}">${c.name}</option>`;
    });
    $('#city').prop('disabled', false).html(opts);
  });
});
</script>
