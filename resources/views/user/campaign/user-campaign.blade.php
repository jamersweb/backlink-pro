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

  
      </form>
      
 
@endsection

