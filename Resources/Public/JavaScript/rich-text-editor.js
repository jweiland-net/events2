/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Small CKEditor 5 for the rich text elements of the frontend form.
 * One editor is created for each textarea with the attribute
 * data-events2-rich-text, which contains the options as JSON.
 * The ES modules of EXT:rte_ckeditor are used. No build step needed.
 * Without JavaScript the plain textarea is used.
 */
import { ClassicEditor } from '@ckeditor/ckeditor5-editor-classic';
import { Essentials } from '@ckeditor/ckeditor5-essentials';
import { Paragraph } from '@ckeditor/ckeditor5-paragraph';
import { Heading } from '@ckeditor/ckeditor5-heading';
import { Bold, Italic } from '@ckeditor/ckeditor5-basic-styles';
import { List } from '@ckeditor/ckeditor5-list';
import { AutoLink, Link } from '@ckeditor/ckeditor5-link';
import { Autoformat } from '@ckeditor/ckeditor5-autoformat';

const SELECTOR = 'textarea[data-events2-rich-text]';

// Used, if the options contain no allowed tags
const DEFAULT_ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'h2', 'h3', 'ul', 'ol', 'li', 'a'];

const HEADING_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

// Toolbar items with the allowed tag and the plugins they need. Groups are separated in the toolbar.
const FEATURES = [
  { item: 'bold', tag: 'strong', group: 'basicStyles', plugins: [Bold] },
  { item: 'italic', tag: 'em', group: 'basicStyles', plugins: [Italic] },
  { item: 'bulletedList', tag: 'ul', group: 'lists', plugins: [List] },
  { item: 'numberedList', tag: 'ol', group: 'lists', plugins: [List] },
  { item: 'link', tag: 'a', group: 'links', plugins: [Link, AutoLink] },
];

/**
 * CKEditor translates the default titles "Paragraph" and "Heading 1" to "Heading 6"
 */
const buildHeadingOptions = (allowedTags) => [
  { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
  ...HEADING_TAGS.filter((tag) => allowedTags.includes(tag)).map((tag) => ({
    model: `heading${tag.substring(1)}`,
    view: tag,
    title: `Heading ${tag.substring(1)}`,
    class: `ck-heading_heading${tag.substring(1)}`,
  })),
];

const buildToolbarItems = (features, hasHeadings) => {
  const items = hasHeadings ? ['heading'] : [];
  features.forEach((feature, index) => {
    if (items.length > 0 && (index === 0 || features[index - 1].group !== feature.group)) {
      items.push('|');
    }
    items.push(feature.item);
  });

  return [...items, ...(items.length > 0 ? ['|'] : []), 'undo', 'redo'];
};

const parseOptions = (textarea) => {
  try {
    return JSON.parse(textarea.dataset.events2RichText || '{}');
  } catch {
    return {};
  }
};

/**
 * The translations of EXT:rte_ckeditor register themselves in window.CKEDITOR_TRANSLATIONS
 */
const loadTranslation = async (translationUrl) => {
  if (!translationUrl) {
    return;
  }
  try {
    await import(translationUrl);
  } catch {
    // Fall back to the English user interface
  }
};

const buildLinkConfiguration = (options) => ({
  allowedProtocols: ['http', 'https', 'mailto', 'tel'],
  defaultProtocol: 'https://',
  decorators: {
    openInNewTab: {
      mode: 'manual',
      label: options.labels?.openInNewTab || 'Open in a new tab',
      attributes: {
        target: '_blank',
        rel: 'noopener noreferrer',
      },
    },
  },
});

const buildConfiguration = (options) => {
  const allowedTags = Array.isArray(options.allowedTags) ? options.allowedTags : DEFAULT_ALLOWED_TAGS;
  const features = FEATURES.filter((feature) => allowedTags.includes(feature.tag));
  const headingOptions = buildHeadingOptions(allowedTags);
  const hasHeadings = headingOptions.length > 1;
  const plugins = [Essentials, Paragraph, Autoformat, ...(hasHeadings ? [Heading] : [])];
  features.forEach((feature) => plugins.push(...feature.plugins.filter((plugin) => !plugins.includes(plugin))));

  return {
    licenseKey: 'GPL',
    language: options.language || 'en',
    plugins,
    toolbar: {
      items: buildToolbarItems(features, hasHeadings),
      // Group items, which do not fit into the toolbar, into a dropdown on small screens
      shouldNotGroupWhenFull: false,
    },
    heading: {
      options: headingOptions,
    },
    link: buildLinkConfiguration(options),
  };
};

const createEditor = async (textarea) => {
  const options = parseOptions(textarea);
  await loadTranslation(options.translationUrl);

  // ClassicEditor updates the textarea when the form is submitted
  await ClassicEditor.create(textarea, buildConfiguration(options));
};

document.querySelectorAll(SELECTOR).forEach((textarea) => {
  createEditor(textarea).catch(() => {
    // Keep the plain textarea, if the editor could not be created
    textarea.hidden = false;
    textarea.style.removeProperty('display');
  });
});
