import i18next from 'i18next';

export const t = (key: string): string => {
  if (!i18next.isInitialized) {
    return key;
  }
  return i18next.t(key);
};