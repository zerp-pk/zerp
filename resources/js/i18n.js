import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const customBackend = {
  type: 'backend',
  init: function(services, backendOptions) {
    this.services = services;
    this.options = backendOptions;
  },
  read: function(language, namespace, callback) {
    const loadPath = window.route ? window.route('languages.translations', language) : `/translations/${language}`;
    
    fetch(loadPath)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
      })
      .then(data => {
        // Fix: Only update dir if this language matches the current intended language
        if (data.layoutDirection && language === (i18n.language || userLang)) {
          document.documentElement.dir = data.layoutDirection;
        }
        callback(null, data.translations);
      })
      .catch(error => {
        callback(error, null);
      });
  }
};

const getInitialLang = () => {
  try {
    const el = document.getElementById('app');
    if (el && el.dataset.page) {
      const initialPage = JSON.parse(el.dataset.page);
      return initialPage.props?.auth?.lang || initialPage.props?.auth?.user?.lang || 'en';
    }
  } catch (e) {
    console.error('i18n: [Init] Error parsing app data:', e);
  }
  return 'en';
};

const userLang = getInitialLang();

i18n
    .use(customBackend)
    .use(initReactI18next)
    .init({
        lng: userLang,
        fallbackLng: userLang,
        interpolation: {
            escapeValue: false,
        }
    });

export default i18n;