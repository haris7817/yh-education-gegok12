<template>
  <div class="bg-white shadow px-4 py-3">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

    <h1>Student Information</h1>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="firstname" class="tw-form-label">English Name<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" class="tw-form-control w-full" id="firstname" v-model="firstname" name="firstname" placeholder="e.g. Chan Siu Ming">
          </div>
          <span v-if="errors.firstname" class="text-red-500 text-xs font-semibold">{{errors.firstname[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="lastname" class="tw-form-label">Chinese Name 中文姓名</label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="lastname" name="lastname" id="lastname" class="tw-form-control w-full" placeholder="例如 陳小明">
          </div>
          <span v-if="errors.lastname" class="text-red-500 text-xs font-semibold">{{errors.lastname[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="date_of_birth" class="tw-form-label">Date Of Birth<span class="text-red-500">*</span>
              <span v-if="computedAge !== null" class="text-gray-500 text-xs"> (Age: {{ computedAge }})</span>
            </label>
          </div>
          <div class="mb-2">
            <input type="date" v-model="date_of_birth" name="date_of_birth" id="date_of_birth" class="tw-form-control w-full">
          </div>
          <span v-if="errors.date_of_birth" class="text-red-500 text-xs font-semibold">{{errors.date_of_birth[0]}}</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="gender" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
          </div>
          <div class="flex tw-form-control">
            <div class="w-1/2 flex items-center mr-2 lg:mr-8 md:mr-8">
              <input type="radio" v-model="gender" name="gender" id="gender1" value="male">
              <span class="text-sm mx-1">Boy</span>
            </div>
            <div class="w-1/2 flex items-center ">
              <input type="radio" v-model="gender" name="gender" id="gender2" value="female">
              <span class="text-sm mx-1">Girl</span>
            </div>
          </div>
          <span v-if="errors.gender" class="text-red-500 text-xs font-semibold">{{errors.gender[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="registration_number" class="tw-form-label">Student ID<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="registration_number" name="registration_number" id="registration_number" class="tw-form-control w-full" placeholder="e.g. YH2026001">
          </div>
          <span v-if="errors.registration_number" class="text-red-500 text-xs font-semibold">{{errors.registration_number[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="mother_tongue" class="tw-form-label">Mother Tongue<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" id="mother_tongue" v-model="mother_tongue" name="mother_tongue">
              <option value="" disabled>Select Mother Tongue</option>
              <option value="Cantonese">Cantonese 廣東話</option>
              <option value="Mandarin">Mandarin 普通話</option>
              <option value="English">English</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <span v-if="errors.mother_tongue" class="text-red-500 text-xs font-semibold">{{errors.mother_tongue[0]}}</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="school_name" class="tw-form-label">School Name (day school)</label>
          </div>
          <div class="mb-2">
            <input type="text" class="tw-form-control w-full" id="school_name" v-model="school_name" name="school_name" placeholder="e.g. TKO Primary School">
          </div>
          <span v-if="errors.school_name" class="text-red-500 text-xs font-semibold">{{errors.school_name[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="grade" class="tw-form-label">Grade (at day school)</label>
          </div>
          <div class="mb-2">
            <input type="text" class="tw-form-control w-full" id="grade" v-model="grade" name="grade" placeholder="e.g. P4 / S2">
          </div>
          <span v-if="errors.grade" class="text-red-500 text-xs font-semibold">{{errors.grade[0]}}</span>
        </div>
      </div>
    </div>

    <GoogleMap
      v-model:address="address"
      v-model:latitude="latitude"
      v-model:longitude="longitude"
    />
    <span v-if="errors.address" class="text-red-500 text-xs font-semibold">{{errors.address[0]}}</span>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="alternate_no" class="tw-form-label">Home Phone</label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="alternate_no" name="alternate_no" id="alternate_no" class="tw-form-control w-full" placeholder="Home Phone (optional)">
          </div>
          <span v-if="errors.alternate_no" class="text-red-500 text-xs font-semibold">{{errors.alternate_no[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="mobile_no" class="tw-form-label">Student Mobile</label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="mobile_no" name="mobile_no" id="mobile_no" class="tw-form-control w-full" placeholder="Mobile Number (optional)">
          </div>
          <span v-if="errors.mobile_no" class="text-red-500 text-xs font-semibold">{{errors.mobile_no[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="email" class="tw-form-label">Email Address</label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="email" name="email" id="email" class="tw-form-control w-full" placeholder="Email (optional)">
          </div>
          <span v-if="errors.email" class="text-red-500 text-xs font-semibold">{{errors.email[0]}}</span>
        </div>
      </div>
    </div>

    <hr style="border-width: 1px;">
    <h1>Guardian / Emergency Contact <span class="text-gray-500 text-sm">(optional)</span></h1>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="guardian_name" class="tw-form-label">Contact Name</label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="guardian_name" name="guardian_name" id="guardian_name" class="tw-form-control w-full" placeholder="Guardian name (中/EN)">
          </div>
          <span v-if="errors.guardian_name" class="text-red-500 text-xs font-semibold">{{errors.guardian_name[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="guardian_relation" class="tw-form-label">Relationship</label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" id="guardian_relation" v-model="guardian_relation" name="guardian_relation">
              <option value="" disabled>Select Relationship</option>
              <option value="father">Father</option>
              <option value="mother">Mother</option>
              <option value="guardian">Guardian</option>
            </select>
          </div>
          <span v-if="errors.guardian_relation" class="text-red-500 text-xs font-semibold">{{errors.guardian_relation[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="guardian_mobile" class="tw-form-label">Contact Phone</label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="guardian_mobile" name="guardian_mobile" id="guardian_mobile" class="tw-form-control w-full" placeholder="e.g. 91234567">
          </div>
          <span v-if="errors.guardian_mobile" class="text-red-500 text-xs font-semibold">{{errors.guardian_mobile[0]}}</span>
        </div>
      </div>
    </div>
    <p class="text-xs text-gray-500">If the phone number matches an existing parent account, the student is linked to it instead of creating a duplicate.</p>

    <hr style="border-width: 1px;">
    <h1>Enrollment</h1>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="standard" class="tw-form-label">Class<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" id="standard" v-model="standard" name="standard">
              <option value="" disabled>Select Class</option>
              <option
                  v-for="standardLink in standardLinklist"
                  :key="standardLink.id"
                  :value="standardLink.id">
                  {{ standardLink.standard_section }}
                </option>
            </select>
          </div>
          <span v-if="errors.standard" class="text-red-500 text-xs font-semibold">{{errors.standard[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="roll_number" class="tw-form-label">Roll Number<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="roll_number" name="roll_number" id="roll_number" class="tw-form-control w-full" placeholder="Roll Number">
          </div>
          <span v-if="errors.roll_number" class="text-red-500 text-xs font-semibold">{{errors.roll_number[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="joining_date" class="tw-form-label">Enrollment Date<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="date" name="joining_date" v-model="joining_date" id="joining_date" class="tw-form-control w-full">
          </div>
          <span v-if="errors.joining_date" class="text-red-500 text-xs font-semibold">{{errors.joining_date[0]}}</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="avatar" class="tw-form-label">Photo <span class="text-gray-500 text-xs">(optional — a default is used if empty)</span></label>
          </div>
          <div class="mb-2">
            <input type="file" name="avatar" @change="OnFileSelected" id="avatar" class="tw-form-control w-full">
          </div>
          <span v-if="errors.avatar" class="text-red-500 text-xs font-semibold">{{errors.avatar[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-2/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="notes" class="tw-form-label">Notes</label>
          </div>
          <div class="mb-2">
            <textarea type="text" class="tw-form-control w-full" v-model="notes" id="notes" name="notes" rows="3"></textarea>
          </div>
          <span v-if="errors.notes" class="text-red-500 text-xs font-semibold">{{errors.notes[0]}}</span>
        </div>
      </div>
    </div>

    <!-- Hidden defaults: Hong Kong location rows + legacy fields kept for compatibility -->
    <input type="hidden" name="country_id" :value="country_id">
    <input type="hidden" name="state_id" :value="state_id">
    <input type="hidden" name="city_id" :value="city_id">
    <input type="hidden" name="siblings" value="no">

    <Teleport to="#submit-btn">
      <div class="my-6">
        <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
        <a href="#" class="btn btn-reset reset-btn" @click="resetForm()">Reset</a>
        <input type="submit" class="hidden" id="real-submit-btn">
      </div>
    </Teleport>
  </div>
</template>

<script>
  import GoogleMap from '../GoogleMap.vue'
export default {
  props:['url'],
  components: {
            GoogleMap
        },

  data(){
    return {
      user:[],
      academic_year_id:'',
      firstname:'',
      lastname:'',
      mobile_no:'',
      email:'',
      gender:'',
      date_of_birth:'',
      standard:'',
      city_id:'',
      state_id:'',
      country_id:'',
      mother_tongue:'',
      joining_date:'',
      registration_number:'',
      roll_number:'',
      school_name:'',
      grade:'',
      alternate_no:'',
      guardian_name:'',
      guardian_relation:'',
      guardian_mobile:'',
      notes:'',
      avatar:'',
      address:'',
      latitude:'',
      longitude:'',
      countrylist:[],
      statelist:[],
      citylist:[],
      standardLinklist:[],
      errors:[],
      success:null,
    }
  },

  computed: {
    computedAge() {
      if (!this.date_of_birth) return null;
      const dob = new Date(this.date_of_birth);
      if (isNaN(dob.getTime())) return null;
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
      return age >= 0 ? age : null;
    }
  },

  methods:
  {
    getData()
    {
      axios.get('/admin/student').then(response => {
        this.user = response.data;
        this.setData();
      });
    },

    setData()
    {
      if(Object.keys(this.user).length>0)
      {
        this.academic_year_id   = this.user.academic_year_id;
        if(this.academic_year_id == null)
        {
          alert("Add Academic Year")
        }
        else
        {
          this.countrylist      = this.user.countrylist;
          this.statelist        = this.user.statelist;
          this.citylist         = this.user.citylist;
          this.standardLinklist = this.user.standardLinklist;
          this.date_of_birth    = this.user.date_of_birth;
          this.joining_date     = this.user.joining_date;
          this.setHongKongDefaults();
        }
      }
    },

    // Location is fixed to Hong Kong (falls back to the first entries if the
    // Hong Kong reference rows are not present).
    setHongKongDefaults()
    {
      const lists = this.countrylist || [];
      const hk = lists.find(c => c.name === 'Hong Kong') || lists[0];
      if (!hk) return;
      this.country_id = hk.id;
      const states = (this.statelist && this.statelist[hk.id]) || [];
      if (states.length > 0) {
        this.state_id = states[0].id;
        const cities = (this.citylist && this.citylist[this.state_id]) || [];
        if (cities.length > 0) {
          this.city_id = cities[0].id;
        }
      }
    },

    resetForm()
    {
      this.firstname='';
      this.lastname='';
      this.mobile_no='';
      this.email='';
      this.gender='';
      this.date_of_birth='';
      this.standard='';
      this.mother_tongue='';
      this.joining_date='';
      this.registration_number='';
      this.roll_number='';
      this.school_name='';
      this.grade='';
      this.alternate_no='';
      this.guardian_name='';
      this.guardian_relation='';
      this.guardian_mobile='';
      this.notes='';
      this.avatar='';
      this.address='';
      this.setHongKongDefaults();
    },

    submitForm()
    {
      this.errors=[];
      this.success=null;

      let formData=new FormData();

      formData.append('firstname',this.firstname);
      formData.append('lastname',this.lastname);
      formData.append('mobile_no',this.mobile_no);
      formData.append('email',this.email);
      formData.append('gender',this.gender);
      formData.append('date_of_birth',this.date_of_birth);
      formData.append('standard',this.standard);
      formData.append('city_id',this.city_id);
      formData.append('state_id',this.state_id);
      formData.append('country_id',this.country_id);
      formData.append('mother_tongue',this.mother_tongue);
      formData.append('joining_date',this.joining_date);
      formData.append('registration_number',this.registration_number);
      formData.append('roll_number',this.roll_number);
      formData.append('school_name',this.school_name);
      formData.append('grade',this.grade);
      formData.append('alternate_no',this.alternate_no);
      formData.append('guardian_name',this.guardian_name);
      formData.append('guardian_relation',this.guardian_relation);
      formData.append('guardian_mobile',this.guardian_mobile);
      formData.append('siblings','no');
      formData.append('notes',this.notes);
      formData.append('avatar',this.avatar);
      formData.append('address', this.address);

      axios.post('/admin/student/add/validationUser',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
        $('#real-submit-btn').click();
      }).catch(error => {
        this.errors = error.response.data.errors;
      });
    },

    OnFileSelected(event)
    {
      this.avatar = event.target.files[0];
    },
  },

  created()
  {
    this.getData();
  }
 }

</script>
