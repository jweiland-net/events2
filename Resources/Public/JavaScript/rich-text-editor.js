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

// h1 is reserved for the title of the event
const HEADING_OPTIONS = [
  { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
  { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
  { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
];

const TOOLBAR_ITEMS = [
  'heading', '|', 'bold', 'italic', '|', 'bulletedList', 'numberedList', '|', 'link', '|', 'undo', 'redo',
];

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

const buildConfiguration = (options) => ({
  licenseKey: 'GPL',
  language: options.language || 'en',
  plugins: [Essentials, Paragraph, Heading, Bold, Italic, List, Link, AutoLink, Autoformat],
  toolbar: {
    items: TOOLBAR_ITEMS,
    // Group items, which do not fit into the toolbar, into a dropdown on small screens
    shouldNotGroupWhenFull: false,
  },
  heading: {
    options: HEADING_OPTIONS,
  },
  link: {
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
  },
});

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
