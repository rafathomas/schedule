# Agendamento público — Page override

Extends `design-system/agendaflow/MASTER.md` for the guest booking journey.

## Product intent

- Complete the booking in six explicit, low-friction steps.
- Show one decision at a time while preserving a compact selection summary.
- Never require an account; ask only for the data needed to reserve and contact the customer.
- Make “any available professional” the fastest route without hiding named professionals.

## Visual hierarchy

- The tenant name and location establish trust before the first choice.
- Progress uses a numbered text label plus a segmented bar; progress is not conveyed by color alone.
- Selection cards are flat, bordered, and use the existing blue brand accent for focus and primary actions.
- Confirmation uses emerald only for success, with a check icon and explicit success text.

## Interaction rules

- Choices advance immediately when the next decision is unambiguous.
- Date selection requires an explicit “Ver horários” action because it triggers a server calculation.
- Slot loading has a visible progress state and stale responses cannot overwrite newer selections.
- The final button is disabled while submitting and the backend revalidates availability.
- Back navigation preserves prior selections and server validation returns the user to the relevant step.

## Responsive behavior

- Mobile: single-column flow, three time slots per row, summary below the primary task.
- Tablet: two-column service/professional cards and four time slots per row.
- Desktop: form and sticky-scale summary use a 1fr/16rem split with a readable maximum width.
- Every interactive target is at least 44px and no step depends on horizontal scrolling.
