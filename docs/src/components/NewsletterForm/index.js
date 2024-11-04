import { useRef } from 'react'
import styles from './styles.module.css'

function NewsletterForm() {
  let inputRef = useRef(null)

  function handleClick() {
    inputRef.current.focus()
  }

  return (
    <div>
      <div
        dangerouslySetInnerHTML={{
          __html: `<script src="https://f.convertkit.com/ckjs/ck.5.js"></script>`,
        }}
      />
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

        <div className={styles.fields}>
          <div className={styles.item}>
            {/*
            <label>First Name
            <input type="text" name="fields[first_name]" required />
            </label>
          */}
            <label
              className="hide"
              htmlFor="email_address"
              style={{
                display: 'none',
              }}
            >
              Email Address
            </label>
            <div>
              <input
                ref={inputRef}
                type="email"
                id="email_address"
                name="email_address"
                placeholder="you@example.com"
                required
                style={{
                  border: '1px solid',
                  borderRight: 0,
                  height: '100%',
                  boxShadow: 'none',
                }}
              />
            </div>
          </div>
          <div className={styles.item}>
            <button className="button button--primary button--lg">
              Subscribe
            </button>
          </div>
        </div>
      </form>
    </div>
  )
}

function NewsletterCard() {
  return (
    <div className="card">
      <div className="card__header">
        <h3>Lorem Ipsum</h3>
      </div>
      <div className="card__body">
        <NewsletterForm />
      </div>
    </div>
  )
}

export default NewsletterCard
