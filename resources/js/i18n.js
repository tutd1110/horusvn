import { createI18n } from 'vue-i18n';

import enMsg from "../lang/en.json";
import vnMsg from "../lang/vn.json";

const i18n = createI18n({
    legacy: false,
    locale: "en",
    globalInjection: true,
    messages: {
      en: {
        message: enMsg
      },
      vn: {
        message: vnMsg
      }
    }
});

  export default i18n;