<template>
  <div class="container profile-page">
    <div v-if="loading">Loading profile...</div>
    <div v-else-if="error">{{ error }}</div>
    <div v-else-if="shipper" class="profile-content">
      <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-4">
          <div class="card profile-card">
            <div class="card-body text-center">
              <div class="profile-image-container">
                 <img :src="avatarUrl" alt="Profile" class="rounded-circle profile-img">
             </div>
              <h2 class="mt-3">{{ shipper.name }}</h2>
              <p class="text-muted">{{ shipper.job_type === 'tech_shipper' ? 'Tech Shipper' : 'Standard Shipper' }}</p>
              <div class="mt-3">
                <span class="badge bg-primary me-2">{{ shipper.status }}</span>
                <span class="badge bg-success">{{ shipper.operating_area || 'No Area Assigned' }}</span>
              </div>
              <div class="mt-3">
                <button class="btn btn-outline-primary btn-sm me-2">
                  <i class="bi bi-star-fill me-1"></i> Score: {{ shipper.vote_score }}
                </button>
                <button class="btn btn-outline-success btn-sm">
                  <i class="bi bi-calendar-check me-1"></i> Attendance: {{ shipper.attendance_score }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Details -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <ul class="nav nav-tabs nav-tabs-bordered" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">Overview</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab" aria-controls="edit" aria-selected="false">Edit Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Change Password</button>
                </li>
              </ul>
              <div class="tab-content pt-4" id="profileTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                  <h5 class="card-title">Profile Details</h5>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">Full Name</div>
                    <div class="col-lg-9 col-md-8">{{ shipper.name }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">Email</div>
                    <div class="col-lg-9 col-md-8">{{ shipper.email }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">Phone</div>
                    <div class="col-lg-9 col-md-8">{{ shipper.phone }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">CCCD</div>
                    <div class="col-lg-9 col-md-8">{{ shipper.cccd }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">City</div>
                    <div class="col-lg-9 col-md-8">{{ shipper.city }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">District</div>
                    <div class="col-lg-9 col-md-8">{{ shipper.district }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 label">Joined Date</div>
                    <div class="col-lg-9 col-md-8">{{ formatDate(shipper.created_at) }}</div>
                  </div>
                </div>

                <!-- Edit Profile Tab -->
                <div class="tab-pane fade" id="edit" role="tabpanel" aria-labelledby="edit-tab">
                <h5 class="card-title">Edit Profile</h5>
                <form @submit.prevent="updateProfile">
                  <div class="row mb-3">
                    <label for="profileImage" class="col-md-4 col-lg-3 col-form-label">Profile Image</label>
                    <div class="col-md-8 col-lg-9">
                      <img :src="avatarUrl" alt="Profile" class="rounded-circle profile-img">
                      <div class="pt-2">
                        <input type="file" @change="onFileChange" ref="fileInput" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-primary btn-sm me-2" @click="$refs.fileInput.click()">
                          <i class="bi bi-upload"></i> Upload
                        </button>
                        <button type="button" v-if="shipper.avatar" class="btn btn-danger btn-sm" @click="removeAvatar">
                          <i class="bi bi-trash"></i> Remove
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                    <div class="col-md-8 col-lg-9">
                      <input type="text" class="form-control" id="fullName" v-model="editedShipper.name">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                    <div class="col-md-8 col-lg-9">
                      <input type="tel" class="form-control" id="phone" v-model="editedShipper.phone">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                    <div class="col-md-8 col-lg-9">
                      <input type="email" class="form-control" id="Email" v-model="editedShipper.email">
                    </div>
                  </div>

                  <div class="text-center">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                  </div>
                </form>
              </div>
                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <h5 class="card-title">Change Password</h5>
                    <form @submit.prevent="changePassword">
                      <div class="row mb-3">
                        <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Current Password</label>
                        <div class="col-md-8 col-lg-9">
                          <input type="password" class="form-control" id="currentPassword" v-model="passwordForm.current_password" required>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">New Password</label>
                        <div class="col-md-8 col-lg-9">
                          <input type="password" class="form-control" id="newPassword" v-model="passwordForm.new_password" required>
                        </div>
                      </div>

                      <div class="row mb-3">
                        <label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Re-enter New Password</label>
                        <div class="col-md-8 col-lg-9">
                          <input type="password" class="form-control" id="renewPassword" v-model="passwordForm.confirm_password" required>
                        </div>
                      </div>

                      <div class="text-center">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                      </div>
                    </form>
                  </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else>No profile data available.</div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useShipperStore } from '@/stores/shipper';
import { useToast } from "vue-toastification";
import { useRouter } from 'vue-router';
export default {
  setup() {
    const toast = useToast();
    const shipperStore = useShipperStore();
    const router = useRouter(); 
    const loading = ref(true);
    const error = ref(null);
    const avatarUrl = computed(() => shipperStore.getShipperAvatar);
    const shipper = computed(() => shipperStore.shipper);
    
    const editedShipper = ref({
      name: '',
      phone: '',
      email: ''
    });
    
    const passwordForm = ref({
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    });


    onMounted(async () => {
      try {
        const isAuthenticated = await shipperStore.checkAuth();
        if (isAuthenticated) {
          await shipperStore.fetchProfile();
          updateEditedShipper();
        } else {
          router.push('/login');
        }
        loading.value = false;
      } catch (err) {
        console.error('Failed to fetch profile:', err);
        error.value = 'Failed to load profile. Please try again.';
        loading.value = false;
      }
    });

    const updateEditedShipper = () => {
      if (shipper.value) {
        editedShipper.value = {
          name: shipper.value.name,
          phone: shipper.value.phone,
          email: shipper.value.email
        };
      }
    };
    const updateProfile = async () => {
      try {
        await shipperStore.updateProfile(editedShipper.value);
        toast.success("Profile updated successfully");
        // Reload the page after a short delay
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } catch (error) {
        console.error('Update failed:', error);
        toast.error("Failed to update profile");
      }
    };

    const onFileChange = async (e) => {
      const file = e.target.files[0];
      if (file) {
        const formData = new FormData();
        formData.append('avatar', file);
        try {
          await shipperStore.uploadAvatar(formData);
          toast.success("Avatar uploaded successfully");
          // Reload the page after a short delay
          setTimeout(() => {
            window.location.reload();
          }, 1);
        } catch (error) {
          console.error('Failed to upload avatar:', error);
          toast.error("Failed to upload avatar");
        }
      }
    };

    const removeAvatar = async () => {
      try {
        await shipperStore.removeAvatar();
        await shipperStore.fetchProfile();
        toast.success("Avatar removed successfully");
      } catch (error) {
        console.error('Avatar removal failed:', error);
        toast.error("Failed to remove avatar");
      }
    };

    const changePassword = async () => {
      if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
        toast.error("New passwords do not match");
        return;
      }
      try {
        await shipperStore.changePassword(passwordForm.value);
        toast.success("Password changed successfully");
        passwordForm.value = { current_password: '', new_password: '', confirm_password: '' };
      } catch (error) {
        console.error('Password change failed:', error);
        if (error.response && error.response.data && error.response.data.errors) {
          Object.values(error.response.data.errors).forEach(errors => {
            errors.forEach(errorMsg => toast.error(errorMsg));
          });
        } else {
          toast.error("Failed to change password");
        }
      }
    };
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    };

    return {
      shipper,
      editedShipper,
      loading,
      error,
      passwordForm,
      updateProfile,
      onFileChange,
      removeAvatar,
      changePassword,
      formatDate,
      avatarUrl,
      
    };
  }
}
</script>


<style scoped>
.profile-image-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  margin-bottom: 1rem;
}
.profile-page {
  padding-top: 2rem;
  height: calc(100vh - 50px); /* Adjust this value based on your header height */
  overflow-y: auto;
}

.profile-content {
  margin-top: 150px; /* This will move the content up by 50px */
}

.profile-card {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  background-color: var(--bg-primary);
  color: var(--text-primary);
}

.profile-img {
  width: 150px;
  height: 150px;
  object-fit: cover;
  border: 4px solid var(--bg-secondary);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  border: none;
  background-color: var(--bg-primary);
  color: var(--text-primary);
}

.nav-tabs-bordered .nav-link.active {
  background-color: var(--bg-primary);
  color: var(--accent-color);
  border-bottom: 3px solid var(--accent-color);
}

.nav-tabs-bordered .nav-link {
  color: var(--text-secondary);
}

.label {
  font-weight: 600;
  color: var(--text-secondary);
}

input.form-control, 
textarea.form-control {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--text-secondary);
}

input.form-control:focus, 
textarea.form-control:focus {
  background-color: var(--bg-secondary);
  color: var(--text-primary);
  border-color: var(--accent-color);
  box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
}

.btn-primary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
}

.btn-primary:hover {
  background-color: darken(var(--accent-color), 10%);
  border-color: darken(var(--accent-color), 10%);
}

.btn-outline-primary {
  color: var(--accent-color);
  border-color: var(--accent-color);
}

.btn-outline-primary:hover {
  background-color: var(--accent-color);
  color: var(--bg-primary);
}

.text-muted {
  color: var(--text-secondary) !important;
}
</style>