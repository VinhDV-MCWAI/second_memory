import type { Metadata } from 'next';
import Link from 'next/link';
import { SkillLevel } from '@/components/skills/skill-level';
import { getPublicSkills, type PublicSkill } from '@/lib/ledger-api';

export const metadata: Metadata = {
  title: 'Skills — Second Memory',
  description: 'Published skills with their current level and evidence',
};

// Rendered per request (data cached by the API client), never at build time when no API runs
export const dynamic = 'force-dynamic';

function groupByCategory(skills: PublicSkill[]): [string, PublicSkill[]][] {
  const groups = new Map<string, PublicSkill[]>();
  for (const skill of skills) {
    groups.set(skill.category, [...(groups.get(skill.category) ?? []), skill]);
  }
  return [...groups.entries()].sort(([a], [b]) => a.localeCompare(b));
}

export default async function SkillsPage() {
  const skills = await getPublicSkills();

  return (
    <main className="mx-auto max-w-3xl px-6 py-12">
      <h1 className="text-3xl font-semibold">Skills</h1>
      <p className="mt-2 text-[hsl(var(--color-muted-foreground))]">
        What I work with, at which level, and the evidence behind it.
      </p>

      {skills.length === 0 ? (
        <p className="mt-10">No skills are published yet.</p>
      ) : (
        groupByCategory(skills).map(([category, items]) => (
          <section key={category} className="mt-10">
            <h2 className="text-xl font-semibold">{category}</h2>
            <ul className="mt-4 divide-y divide-[hsl(var(--color-border))]">
              {items.map((skill) => (
                <li key={skill.slug} className="py-4">
                  <Link href={`/skills/${skill.slug}`} className="font-medium hover:underline">
                    {skill.name}
                  </Link>
                  <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                    <SkillLevel level={skill.current_level} label={skill.current_level_label} />
                    {skill.tags.length > 0 && (
                      <span className="text-sm text-[hsl(var(--color-muted-foreground))]">
                        {skill.tags.join(' · ')}
                      </span>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          </section>
        ))
      )}
    </main>
  );
}
