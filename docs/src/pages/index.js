import React from 'react';
import clsx from 'clsx';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import useBaseUrl from '@docusaurus/useBaseUrl';
import styles from './styles.module.css';

const features = [
  {
    title: 'Set Payees',
    imageUrl: null,
    description: (
      <>Let Users sell Craft Commerce Products, configured in the admin area, or your front&#8209;end.</>
    ),
  },
  {
    title: 'Set a Platform Fee',
    imageUrl: null,
    description: (
      <>
        Recieve a flat-rate or percentage-based fee on sales your Platform makes.
      </>
    ),
  },
  {
    title: 'Split Payments',
    imageUrl: null,
    description: (
      <>Add Stripe Connect to the <a href="https://plugins.craftcms.com/commerce-stripe" target="_blank" rel="noopener">Stripe Payment Gateway</a> you already use to process payments.</>
    ),
  },
];

// 

function Feature({imageUrl, title, description}) {
  const imgUrl = useBaseUrl(imageUrl);
  return (
    <div className={clsx('col col--4', styles.feature)}>
      {imgUrl && (
        <div className="text--center">
          <img className={styles.featureImage} src={imgUrl} alt={title} />
        </div>
      )}
      <h3>{title}</h3>
      <p>{description}</p>
    </div>
  );
}

export default function Home() {
  const context = useDocusaurusContext();
  const {siteConfig = {}} = context;
  return (
    <Layout
      title="Marketplace for Craft Commerce"
      description="Make your Craft CMS and Commerce site into a Marketplace, using Stripe Connect. Add payees to products, charge a platform fee, and handle payouts automatically.">
      <header className={clsx('hero', styles.heroBanner)}>
        <div className="container">
          <h1 className="hero__title">{siteConfig.title}</h1>
          <p className="hero__subtitle">{siteConfig.tagline}</p>
          <div className={styles.buttons}>
            <Link
              className={clsx(
                'button button--primary button--lg',
                styles.getStarted,
              )}
              to={useBaseUrl('docs/')}>
              Get Started
            </Link>
          </div>
        </div>
      </header>
      <main>
        {features && features.length > 0 && (
          <section className={styles.features}>
            <div className="container">
              <div className="row">
                {features.map((props, idx) => (
                  <Feature key={idx} {...props} />
                ))}
              </div>
            </div>
          </section>
        )}
      </main>
    </Layout>
  );
}
