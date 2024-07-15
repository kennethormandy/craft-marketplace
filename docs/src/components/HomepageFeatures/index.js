import clsx from 'clsx'
import Heading from '@theme/Heading'
import styles from './styles.module.css'

const FeatureList = [
  {
    title: 'Set Payees',
    Svg: null,
    description: (
      <>
        Let Users sell Craft Commerce Products, configured in the admin area, or
        your front&#8209;end.
      </>
    ),
  },
  {
    title: 'Set a Platform Fee',
    Svg: null,
    description: (
      <>
        Recieve a flat-rate or percentage-based fee on sales your Platform
        makes.
      </>
    ),
  },
  {
    title: 'Split Payments',
    Svg: null,
    description: (
      <>
        Add Stripe Connect to the{' '}
        <a
          href="https://plugins.craftcms.com/commerce-stripe"
          target="_blank"
          rel="noopener"
        >
          Stripe Payment Gateway
        </a>{' '}
        you already use to process payments.
      </>
    ),
  },
]

function Feature({ Svg, title, description }) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center">
        {Svg && <Svg className={styles.featureSvg} role="img" />}
      </div>
      <div>
        <Heading as="h3">{title}</Heading>
        <p>{description}</p>
      </div>
    </div>
  )
}

export default function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  )
}
