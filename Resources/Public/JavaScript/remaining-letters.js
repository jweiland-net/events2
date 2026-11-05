/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Shows the remaining letters below each textarea with the attributes
 * maxlength and data-events2-remaining-letters. The data attribute contains
 * the translated label, where "%s" is replaced with the remaining letters.
 * Without JavaScript the maxlength and the validators of the form still apply.
 */
const SELECTOR = 'textarea[maxlength][data-events2-remaining-letters]';

const PLACEHOLDER = '%s';

const addDescribedBy = (textarea, id) => {
  const ids = (textarea.getAttribute('aria-describedby') || '').split(' ').filter((value) => value !== '');
  textarea.setAttribute('aria-describedby', [...ids, id].join(' '));
};

const initializeCounter = (textarea) => {
  const { maxLength } = textarea;
  if (maxLength <= 0 || textarea.dataset.events2RemainingLettersInitialized === '1') {
    return;
  }

  const label = textarea.dataset.events2RemainingLetters;
  const counter = document.createElement('p');
  counter.className = 'events2-remaining-letters';
  counter.id = `${textarea.id || textarea.name}-remaining-letters`;
  counter.setAttribute('aria-live', 'polite');

  const update = () => {
    counter.textContent = label.replace(PLACEHOLDER, String(Math.max(0, maxLength - textarea.value.length)));
  };

  textarea.after(counter);
  addDescribedBy(textarea, counter.id);
  textarea.addEventListener('input', update);
  textarea.dataset.events2RemainingLettersInitialized = '1';
  update();
};

document.querySelectorAll(SELECTOR).forEach(initializeCounter);
