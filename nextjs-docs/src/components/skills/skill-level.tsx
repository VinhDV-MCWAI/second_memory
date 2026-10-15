import { MAX_LEVEL } from '@/lib/ledger-api';

interface SkillLevelProps {
  level: number;
  label: string;
}

// Level as filled dots plus its label, e.g. ●●●○ Independent (3/4).
export function SkillLevel({ level, label }: SkillLevelProps) {
  return (
    <span className="inline-flex items-center gap-2 text-sm">
      <span aria-hidden="true" className="flex gap-1">
        {Array.from({ length: MAX_LEVEL }, (_, index) => (
          <span
            key={index}
            className={`size-2 rounded-full ${
              index < level ? 'bg-[hsl(var(--color-primary))]' : 'bg-[hsl(var(--color-border))]'
            }`}
          />
        ))}
      </span>
      <span>
        {label} ({level}/{MAX_LEVEL})
      </span>
    </span>
  );
}
