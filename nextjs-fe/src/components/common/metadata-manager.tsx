'use client';

import { useEffect } from 'react';
import { useTranslations } from 'next-intl';

export function MetadataManager() {
  const t = useTranslations('metadata');

  useEffect(() => {
    document.title = t('title');
    
    // Add or update meta description
    let metaDescription = document.querySelector('meta[name="description"]');
    if (!metaDescription) {
      metaDescription = document.createElement('meta');
      metaDescription.setAttribute('name', 'description');
      document.head.appendChild(metaDescription);
    }
    metaDescription.setAttribute('content', t('description'));
  }, [t]);

  return null;
}
