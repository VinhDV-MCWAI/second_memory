import { getRequestConfig } from 'next-intl/server';
import { readFileSync } from 'fs';
import { join } from 'path';

export default getRequestConfig(async () => {
  let locale = 'en';

  if (typeof window !== 'undefined') {
    locale = localStorage.getItem('locale') || 'en';
  }
  
  const messagesPath = join(process.cwd(), 'messages', `${locale}.json`);
  const messages = JSON.parse(readFileSync(messagesPath, 'utf8'));
  
  return {
    locale,
    messages
  };
});
