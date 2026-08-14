# Agenda — Page override

Extends `design-system/agendaflow/MASTER.md` for the daily scheduling workspace.

## Product intent

- The weekly view is the default operational surface; daily view is one click away.
- On narrow screens, days become a vertical card stream instead of a horizontally compressed calendar.
- Appointment cards expose time, status, customer, service, and professional in that order.
- Manual booking is progressive: customer, professional, service, date, then server-calculated slot.

## Visual hierarchy

- Blue is reserved for primary actions, current-day emphasis, and confirmed appointments.
- Status colors always pair foreground, background, and border; color is never the only signal because every card includes a text label.
- Blocks use neutral dashed borders so they read as unavailable periods rather than appointments.
- Cards use the base radius and border from the master system; no decorative gradients.

## Interaction rules

- All touch targets are at least 44px.
- Date navigation preserves the selected view and professional filter.
- Availability requests show an inline loading state and stale responses cannot replace newer choices.
- Appointment and block forms retain server validation messages next to their fields.
- Modal content scrolls within the viewport on short screens.

## Responsive behavior

- Mobile: one day per row, full-width actions, no horizontal schedule overflow.
- Tablet: two day cards per row.
- Desktop: four columns, expanding to seven only on wide displays where cards remain legible.
- Landscape mobile retains a two-column layout when width permits while forms remain vertically scrollable.
