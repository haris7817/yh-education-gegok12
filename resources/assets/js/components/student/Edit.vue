<template>
  <div class="bg-white shadow px-4 py-3">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <h1>Student Information</h1>
    <div class="my-6">
      <div class="flex items-center">
        <img :src="this.avatar_display" class="img-responsive w-12 h-12 rounded-full">
        <div class="mx-2">
          <h2 class="font-semibold text-sm text-gray-700">{{ user.firstname }} {{ user.lastname }}</h2>
          <label class="tw-label cursor-pointer text-xs text-gray-600"> Change Photo
            <input type="file" size="60" name="avatar" id="avatar" @change="OnFileSelected">
            <span v-if="errors.avatar" class="text-red-500 text-xs font-semibold">{{errors.avatar[0]}}</span>
          </label>
        </div>
      </div>
    </div>

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
          <div class="flex">
            <div class="w-1/2 flex items-center tw-form-control mr-2 lg:mr-8 md:mr-8">
              <input type="radio" v-model="gender" name="gender" id="gender1" value="male">
              <span class="text-sm mx-1">Boy</span>
            </div>
            <div class="w-1/2 flex items-center tw-form-control lg:mr-8 md:mr-8">
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
      <div class="tw-form-group w-full lg:w-1/2">
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

    <!-- Hidden defaults kept for compatibility with the existing backend -->
    <input type="hidden" name="country_id" :value="country_id">
    <input type="hidden" name="state_id" :value="state_id">
    <input type="hidden" name="city_id" :value="city_id">
    <input type="hidden" name="siblings" :value="siblings || 'no'">

    <Teleport to="#submit-btn">
      <div class="my-6">
        <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
        <input type="submit" class="hidden" id="real-submit-btn">
      </div>
    </Teleport>
  </div>
</template>

<script>
  import GoogleMap from '../GoogleMap.vue'
export default {
  props:['url' , 'student_name'],
    components: {
        GoogleMap
    },

  data(){
    return {
      user:[],
      firstname:'',
      lastname:'',
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
      siblings:'',
      notes:'',
      avatar:'',
      avatar_display:'',
      address:'',
      latitude:'',
      longitude:'',
      today:'',
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
      axios.get('/admin/student/editStudent/'+this.student_name).then(response => {
        this.user = response.data;
        this.setData();
      });
    },

    setData()
    {
      if(Object.keys(this.user).length>0)
      {
        this.firstname            = this.user.firstname;
        this.lastname             = this.user.lastname;
        this.date_of_birth        = this.user.date_of_birth;
        this.gender               = this.user.gender;
        this.city_id              = this.user.city_id;
        this.state_id             = this.user.state_id;
        this.country_id           = this.user.country_id;
        this.mother_tongue        = this.user.mother_tongue;
        this.avatar_display       = this.user.avatar;
        this.notes                = this.user.notes;
        this.registration_number  = this.user.registration_number;
        this.joining_date         = this.user.joining_date;
        this.address              = this.user.address;
        this.alternate_no         = this.user.alternate_no;
        this.school_name          = this.user.school_name;
        this.grade                = this.user.grade;
        this.guardian_name        = this.user.guardian_name;
        this.guardian_relation    = this.user.guardian_relation;
        this.guardian_mobile      = this.user.guardian_mobile;

        this.standard             = this.user.standardLink_id;
        this.roll_number          = this.user.roll_number;
        this.siblings             = this.user.siblings;

        this.countrylist          = this.user.countrylist;
        this.statelist            = this.user.statelist;
        this.citylist             = this.user.citylist;
        this.standardLinklist     = this.user.standardLinklist;
        this.today                = this.user.today;

        // Older records may predate the location data — normalize to Hong Kong.
        if (!this.country_id) {
          const hk = (this.countrylist || []).find(c => c.name === 'Hong Kong') || (this.countrylist || [])[0];
          if (hk) {
            this.country_id = hk.id;
            const states = (this.statelist && this.statelist[hk.id]) || [];
            if (states.length > 0) {
              this.state_id = states[0].id;
              const cities = (this.citylist && this.citylist[this.state_id]) || [];
              if (cities.length > 0) this.city_id = cities[0].id;
            }
          }
        }
      }
    },

    submitForm()
    {
      this.errors=[];
      this.success=null;

      let formData=new FormData();

      formData.append('firstname',this.firstname);
      formData.append('lastname',this.lastname);
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
      formData.append('siblings',this.siblings || 'no');
      formData.append('notes',this.notes);
      formData.append('avatar',this.avatar);
      formData.append('address', this.address);

      axios.post('/admin/student/edit/validationUser/'+this.student_name,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
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

<style scoped>
  .tw-label{
    color:#3492e2;
  }
  .tw-label input[type="file"] {
    display: none;
  }
</style>
