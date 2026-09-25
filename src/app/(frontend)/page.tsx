import { getSiteSettings } from '@/lib/queries/siteSettings'

export default async function HomePage() {
  const settings = await getSiteSettings()
  const interests = settings.interests ?? []

  return (
    <main className="route-main home-main">
      <section
        className="hero motion-in"
        aria-label={`Hello, I'm ${settings.authorDisplayName}`}
      >
        <div className="hero-top">
          <span className="eyebrow">A little corner of the internet</span>
          <span className="hero-mark" aria-hidden="true">
            ✳
          </span>
        </div>
        <h1>{settings.heroGreeting}</h1>
        <div className="hero-bottom">
          <p>
            Thinking out loud.
            <br />
            Figuring things out.
          </p>
          <span className="edition">THE PERSONAL EDITION</span>
        </div>
      </section>
      <section className="intro" aria-labelledby="intro-title">
        <div className="intro-top eyebrow">
          <span className="line" aria-hidden="true" />
          Behind the words
        </div>
        <div>
          <h2 id="intro-title">{settings.introHeading}</h2>
          <p className="intro-description">{settings.introBody}</p>
        </div>
        {interests.length > 0 && (
          <div className="topics">
            <p className="topics-label eyebrow">Things on my mind</p>
            <ul className="topic-list">
              {interests.map((interest) => (
                <li key={interest} className="topic">
                  {interest}
                </li>
              ))}
            </ul>
          </div>
        )}
      </section>
    </main>
  )
}
