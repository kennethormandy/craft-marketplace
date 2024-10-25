import clsx from 'clsx'
import Heading from '@theme/Heading'
import styles from './styles.module.css'

const FeatureList = [
  {
    title: 'Onboard Accounts',
    Svg: null,
    description: (
      <>Let vendors recieve payouts from your platform without manual work from you, via Stripe Connect.</>
    ),
  },
  {
    title: 'Set a Platform Fee',
    Svg: null,
    description: (
      <>
        Keep a simple percentage fee for all sales, or complete customize your
        context-dependent fee.
      </>
    ),
  },
  {
    title: 'Split Payments',
    Svg: null,
    description: (
      <>
        Marketplace adds support for split payments to the <a
          href="https://plugins.craftcms.com/commerce-stripe"
          target="_blank"
          rel="noopener"
        >
          Stripe Payment Gateway
        </a>{' '}
        you already use.
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
