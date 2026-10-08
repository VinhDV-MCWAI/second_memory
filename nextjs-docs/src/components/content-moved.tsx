// Shown for /docs and every former docs URL since the CMS was removed (REQ-001 US-4).
export function ContentMoved() {
  return (
    <main className="flex min-h-screen items-center justify-center px-6">
      <div className="max-w-md text-center">
        <h1 className="text-2xl font-semibold">This content has moved</h1>
        <p className="mt-4 text-[hsl(var(--color-muted-foreground))]">
          The documentation that used to live here is no longer published on this site. It will
          return as part of a portfolio site.
        </p>
      </div>
    </main>
  );
}
