import Vue from "vue";
import ScreenListing from "./components/ScreenListing";
import CreateScreenModal from "./components/CreateScreenModal";
import CategorySelect from "../categories/components/CategorySelect";

Vue.component("CategorySelect", CategorySelect);

new Vue({
  el: "#screenIndex",
  components: {
    CreateScreenModal,
    ScreenListing,
  },
  data: {
    filter: "",
    screenModal: false,
    screenId: null,
  },
  mounted() {
    const searchParams = new URLSearchParams(window.location.search);
    if (searchParams.size > 0 && searchParams.get("create") === "true") {
      this.$root.$emit("bv::show::modal", "createScreen", "#createScreenModalButton");
    }
  },
  methods: {
    goToImport() {
      window.location = "/designer/screens/import";
    },
    show() {
      this.screenId = null;
      this.screenModal = true;
    },
    reload() {
      this.$refs.screenListing.dataManager([
        {
          field: "updated_at",
          direction: "desc",
        },
      ]);
    },
  },
});
