<template>
  <div>
    <b-button
      v-if="!hideAddBtn && !callFromAiModeler"
      ref="createScriptModalButton"
      v-b-modal.createScript
      :aria-label="$t('Create Script')"
      class="mb-3 mb-md-0 ml-md-2"
    >
      <i class="fas fa-plus" /> {{ $t("Script") }}
    </b-button>
    <modal
      id="createScript"
      :ok-disabled="disabled"
      :title="modalSetUp"
      @hidden="onClose"
      @ok.prevent="onSubmit"
    >
      <template v-if="countCategories">
        <required />
        <b-form-group
          :description="
            formDescription(
              'The script name must be unique.',
              'title',
              addError
            )
          "
          :invalid-feedback="errorMessage('title', addError)"
          :label="$t('Name')"
          :state="errorState('title', addError)"
          required
        >
          <b-form-input
            v-model="title"
            :state="errorState('title', addError)"
            autocomplete="off"
            autofocus
            name="title"
            required
          />
        </b-form-group>
        <b-form-group
          :invalid-feedback="errorMessage('description', addError)"
          :label="$t('Description')"
          :state="errorState('description', addError)"
          required
        >
          <b-form-textarea
            v-model="description"
            :state="errorState('description', addError)"
            autocomplete="off"
            name="description"
            required
            rows="2"
          />
        </b-form-group>
        <category-select
          v-show="!projectAsset"
          v-model="script_category_id"
          :errors="addError.script_category_id"
          :label="$t('Category')"
          api-get="script_categories"
          api-list="script_categories"
          name="script_category_id"
        />
        <project-select
          v-if="isProjectsInstalled"
          v-model="projects"
          :errors="addError.projects"
          :label="$t('Project')"
          :required="isProjectSelectionRequired"
          api-get="projects"
          api-list="projects"
          name="project"
          :project-id="projectId"
        />
        <b-form-group
          :invalid-feedback="errorMessage('script_executor_id', addError)"
          :label="$t('Language')"
          :state="errorState('script_executor_id', addError)"
          :disabled="copyAssetMode"
          required
        >
          <b-form-select
            v-model="script_executor_id"
            :options="scriptExecutors"
            :state="errorState('script_executor_id', addError)"
            :disabled="copyAssetMode"
            name="script_executor_id"
            required
          />
        </b-form-group>
        <b-form-group
          :description="
            formDescription(
              'Select a user to set the API access of the Script',
              'run_as_user_id',
              addError
            )
          "
          :invalid-feedback="errorMessage('run_as_user_id', addError)"
          :label="$t('Run script as')"
          :state="errorState('run_as_user_id', addError)"
          required
        >
          <select-user
            v-model="selectedUser"
            :class="{
              'is-invalid': errorState('run_as_user_id', addError) == false,
            }"
            :multiple="false"
            name="run_as_user_id"
          />
        </b-form-group>
        <slider-with-input
          :description="
            $t(
              'How many seconds the script should be allowed to run (0 is unlimited).'
            )
          "
          :error="
            errorState('timeout', addError)
              ? null
              : errorMessage('timeout', addError)
          "
          :label="$t('Timeout')"
          :max="300"
          :min="0"
          :value="timeout"
          @input="timeout = $event"
        />
        <slider-with-input
          :description="
            $t(
              'Number of times to retry. Leave empty to use script default. Set to 0 for no retry attempts.'
            )
          "
          :error="
            errorState('retry_attempts', addError)
              ? null
              : errorMessage('retry_attempts', addError)
          "
          :label="$t('Retry Attempts')"
          :max="10"
          :min="0"
          :value="retry_attempts"
          @input="retry_attempts = $event"
        />
        <slider-with-input
          :description="
            $t(
              'Seconds to wait before retrying. Leave empty to use script default. Set to 0 for no retry wait time.'
            )
          "
          :error="
            errorState('retry_wait_time', addError)
              ? null
              : errorMessage('retry_wait_time', addError)
          "
          :label="$t('Retry Wait Time')"
          :max="3600"
          :min="0"
          :value="retry_wait_time"
          @input="retry_wait_time = $event"
        />
        <component
          :is="cmp"
          v-for="(cmp, index) in createScriptHooks"
          :key="`create-script-hook-${index}`"
          ref="createScriptHooks"
          :script="script"
        />
      </template>
      <template v-else>
        <div>{{ $t("Categories are required to create a script") }}</div>
        <a
          class="btn btn-primary container mt-2"
          href="/designer/scripts/categories"
        >
          {{ $t("Add Category") }}
        </a>
      </template>
    </modal>
  </div>
</template>

<script>
import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
import Modal from "../../../components/shared/Modal.vue";
import Required from "../../../components/shared/Required.vue";
import ProjectSelect from "../../../components/shared/ProjectSelect.vue";
import SliderWithInput from "../../../components/shared/SliderWithInput.vue";
import { isQuickCreate as isQuickCreateFunc } from "../../../utils/isQuickCreate";

const channel = new BroadcastChannel("assetCreation");

export default {
  components: {
    Modal,
    Required,
    SliderWithInput,
    ProjectSelect,
  },
  mixins: [FormErrorsMixin],
  props: [
    "countCategories",
    "scriptExecutors",
    "isProjectsInstalled",
    "hideAddBtn",
    "copyAssetMode",
    "projectAsset",
    "assetName",
    "callFromAiModeler",
    "isProjectSelectionRequired",
    "projectId",
    "assetData",
  ],
  data() {
    return {
      title: "",
      language: "",
      script_executor_id: null,
      description: "",
      script_category_id: "",
      category_type_id: "",
      code: "",
      addError: {},
      selectedUser: "",
      users: [],
      timeout: 60,
      retry_attempts: 0,
      retry_wait_time: 5,
      disabled: false,
      createScriptHooks: [],
      script: null,
      projects: [],
      isQuickCreate: isQuickCreateFunc(),
    };
  },
  computed: {
    modalSetUp() {
      if (this.copyAssetMode) {
        this.title = `${this.assetName} ${this.$t("Copy")}`;
        this.script_executor_id = this.assetData.script_executor_id;
        this.description = this.assetData.description;
        this.script_category_id = this.assetData.script_category_id;
        this.run_as_user_id = this.assetData.selectedUser
          ? this.assetData.selectedUser.id
          : null;
        this.projects = this.assetData.projects;
        this.code = this.assetData.code;
        this.timeout = this.assetData.timeout;
        this.retry_attempts = this.assetData.retry_attempts;
        this.retry_wait_time = this.assetData.retry_wait_time;
        return this.$t("Copy of Asset");
      }
      this.title = "";
      return this.$t("Create Script");
    },
  },
  methods: {
    show() {
      this.$bvModal.show("createScript");
    },
    onClose() {
      this.title = "";
      this.language = "";
      this.script_executor_id = null;
      this.description = "";
      this.script_category_id = "";
      this.category_type_id = "";
      this.code = "";
      this.timeout = 60;
      this.retry_attempts = 0;
      this.retry_wait_time = 5;
      this.addError = {};
    },
    close() {
      this.$bvModal.hide("createScript");
      this.disabled = false;
      this.$emit("reload");
    },
    onSubmit() {
      this.errors = {
        name: null,
        description: null,
        status: null,
        script_category_id: null,
      };
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;

      ProcessMaker.apiClient
        .post("/scripts", {
          title: this.title,
          script_executor_id: this.script_executor_id,
          description: this.description,
          script_category_id: this.script_category_id,
          run_as_user_id: this.selectedUser ? this.selectedUser.id : null,
          projects: this.projects,
          code: "[]",
          timeout: this.timeout,
          retry_attempts: this.retry_attempts,
          retry_wait_time: this.retry_wait_time,
        })
        .then(({ data }) => {
          ProcessMaker.alert(this.$t("The script was created."), "success");
          (this.$refs.createScriptHooks || []).forEach((hook) => {
            hook.onsave(data);
          });

          const url = `/designer/scripts/${data.id}/builder`;

          if (this.callFromAiModeler) {
            this.$emit("script-created-from-modeler", url, data.id, data.title);
          } else if (this.copyAssetMode) {
            this.close();
          } else {
            if (this.isQuickCreate === true) {
              channel.postMessage({
                assetType: "script",
                asset: data,
              });
            }
            window.location = url;
          }
        })
        .catch((error) => {
          this.disabled = false;
          if (_.get(error, "response.status") === 422) {
            this.addError = error.response.data.errors;
          } else {
            throw error;
          }
        });
    },
  },
};
</script>
