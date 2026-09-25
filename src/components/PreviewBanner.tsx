export function PreviewBanner({ exitTo }: { exitTo: string }) {
  return (
    <div className="preview-banner" role="status">
      <span>Draft preview — only you can see this. Visitors still see the published version.</span>
      <a href={`/preview/exit?to=${encodeURIComponent(exitTo)}`}>Exit preview</a>
    </div>
  )
}
