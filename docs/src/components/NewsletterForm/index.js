import { useRef } from 'react'
import clsx from 'clsx'
import styles from './styles.module.css'

function NewsletterForm(props) {
  let inputRef = useRef(null)

  function handleClick() {
    inputRef.current.focus()
  }

  return (
    <div className={clsx('card', styles.card)} onClick={handleClick}>
      <div
        dangerouslySetInnerHTML={{
          __html: `<script src="https://f.convertkit.com/ckjs/ck.5.js"></script>`,
        }}
      />
      <div className={clsx('card__header', styles.cardHeader)}>
        <h3>{props.header}</h3>
      </div>
      <div className={clsx('card__body', styles.cardBody)}>
        <p>
          {props.body}
        </p>
      </div>
      <div className={clsx('card__footer', styles.cardFooter)}>
        <form // eslint-disable-line jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events
          onClick={handleClick}
          action="https://app.kit.com/forms/7323596/subscriptions"
          method="post"
          data-sv-form="7323596"
          data-uid="f81d8f3e0c"
        >
          <div
            dangerouslySetInnerHTML={{
              __html: `<div style="display: none;" aria-hidden="true">
            <label for="website">Website</label><br>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="false" value="">
          </div>`,
            }}
          />

          <div className={styles.newsletterFields}>
            <div className={styles.newsletterFieldItem}>
              {/*
            <label>First Name
            <input type="text" name="fields[first_name]" required />
            </label>
          */}
              <label className={styles.newsletterLabel} htmlFor="email_address">
                Email Address
              </label>
              <input
                className={styles.newsletterInput}
                ref={inputRef}
                type="email"
                id="email_address"
                name="email_address"
                placeholder="you@example.com"
                required
              />
            </div>
            <div className={styles.item}>
              <button
                className={clsx(
                  'button button--primary button--lg',
                  styles.newsletterButton
                )}
              >
                {props.buttonLabel}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  )
}

NewsletterForm.defaultProps = {
  header: 'Quick emails for Craft CMS developers',
  body: 'Build marketplaces, applications, and other complex products with Craft CMS. Concise emails to help you get it done.',
  buttonLabel: 'Subscribe',
}

export default NewsletterForm
