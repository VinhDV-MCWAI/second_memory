import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { SkillLevel } from '@/components/skills/skill-level';
import { getPublicSkill } from '@/lib/ledger-api';

interface SkillPageProps {
  params: Promise<{ slug: string }>;
}

// Rendered per request (data cached by the API client), never at build time when no API runs
export const dynamic = 'force-dynamic';

export async function generateMetadata({ params }: SkillPageProps): Promise<Metadata> {
  const skill = await getPublicSkill((await params).slug);
  return { title: skill ? `${skill.name} — Skills` : 'Skill not found' };
}

// A private skill and a missing one both answer 404 (REQ-002 US-3).
export default async function SkillPage({ params }: SkillPageProps) {
  const skill = await getPublicSkill((await params).slug);
  if (!skill) {
    notFound();
  }

  return (
    <main className="mx-auto max-w-3xl px-6 py-12">
      <Link href="/skills" className="text-sm hover:underline">
        ← All skills
      </Link>
      <h1 className="mt-4 text-3xl font-semibold">{skill.name}</h1>
      <p className="mt-1 text-[hsl(var(--color-muted-foreground))]">{skill.category}</p>
      <div className="mt-3">
        <SkillLevel level={skill.current_level} label={skill.current_level_label} />
      </div>
      {skill.description && <p className="mt-6 whitespace-pre-line">{skill.description}</p>}

      <section className="mt-10">
        <h2 className="text-xl font-semibold">Level history</h2>
        <ol className="mt-4 space-y-2">
          {skill.history.map((change) => (
            <li key={`${change.changed_on}-${change.level}`} className="flex gap-4 text-sm">
              <time dateTime={change.changed_on} className="w-28 shrink-0 font-mono">
                {change.changed_on}
              </time>
              <span>
                {change.level_label} ({change.level})
              </span>
            </li>
          ))}
        </ol>
      </section>

      <section className="mt-10">
        <h2 className="text-xl font-semibold">Evidence</h2>
        {skill.evidence.length === 0 ? (
          <p className="mt-4 text-sm">No published evidence yet.</p>
        ) : (
          <ul className="mt-4 space-y-4">
            {skill.evidence.map((item) => (
              <li key={`${item.url}-${item.occurred_on}`}>
                <div className="flex flex-wrap items-baseline gap-x-3">
                  <span className="rounded bg-[hsl(var(--color-muted))] px-1.5 py-0.5 font-mono text-xs uppercase">
                    {item.type}
                  </span>
                  <a
                    href={item.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="font-medium hover:underline"
                  >
                    {item.title}
                  </a>
                  <time dateTime={item.occurred_on} className="font-mono text-sm">
                    {item.occurred_on}
                  </time>
                </div>
                {item.summary && <p className="mt-1 text-sm">{item.summary}</p>}
              </li>
            ))}
          </ul>
        )}
      </section>
    </main>
  );
}
