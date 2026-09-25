type SocialLink = {
  label: string
  url: string
}

export function Footer({
  copyrightName,
  socialLinks,
}: {
  copyrightName: string
  socialLinks: SocialLink[]
}) {
  const year = new Date().getFullYear()

  return (
    <footer className="site-footer">
      <p>
        © {year} {copyrightName}
      </p>
      <div className="footer-right">
        <span>Always a work in progress.</span>
        {socialLinks.map((link) => (
          <a key={link.url} href={link.url} target="_blank" rel="noopener noreferrer">
            {link.label}
          </a>
        ))}
      </div>
    </footer>
  )
}
