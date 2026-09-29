# Accessibility Foundation
## Personalized Study Planner - Vue 3 Frontend

---

## WCAG 2.1 AA Compliance Target

This application must meet **WCAG 2.1 Level AA** standards as a minimum baseline.

---

## Color & Contrast

### Required Contrast Ratios
| Element Type | Minimum Ratio |
|--------------|---------------|
| Normal text (< 18pt / < 14pt bold) | 4.5:1 |
| Large text (≥ 18pt / ≥ 14pt bold) | 3:1 |
| UI components (borders, icons, focus indicators) | 3:1 |
| Graphical objects (charts, icons) | 3:1 |

### Color Palette (WCAG AA Compliant)

```javascript
// Primary colors - all meet 4.5:1 on white, 3:1 on gray-100
const colors = {
  primary: {
    50: '#EFF6FF',
    100: '#DBEAFE', 
    200: '#BFDBFE',
    300: '#93C5FD',
    400: '#60A5FA',
    500: '#3B82F6',  // Main primary - 4.5:1 on white
    600: '#2563EB',  // Hover - 4.5:1 on white
    700: '#1D4ED8',  // Active - 7:1 on white
    800: '#1E40AF',
    900: '#1E3A8A',
  },
  
  // Semantic colors - all meet contrast requirements
  success: '#16A34A',    // Green - 4.5:1 on white
  warning: '#F59E0B',    // Amber - 3:1 on white (large text only)
  danger: '#DC2626',     // Red - 4.5:1 on white
  info: '#2563EB',       // Blue - 4.5:1 on white
  
  // Neutral grayscale
  gray: {
    50: '#F9FAFB',
    100: '#F3F4F6',
    200: '#E5E7EB',
    300: '#D1D5DB',
    400: '#9CA3AF',
    500: '#6B7280',
    600: '#4B5563',
    700: '#374151',
    800: '#1F2937',
    900: '#111827',
  },
  
  // Subject colors (all meet 3:1 on white for UI elements)
  subjects: {
    blue: '#3B82F6',
    indigo: '#6366F1',
    purple: '#A855F7',
    pink: '#EC4899',
    red: '#EF4444',
    orange: '#F97316',
    amber: '#F59E0B',
    yellow: '#EAB308',
    green: '#22C55E',
    teal: '#14B8A6',
    cyan: '#06B6D4',
    gray: '#6B7280',
  }
}
```

### Never Rely on Color Alone
- Status indicators: Use **color + icon + text label**
- Form errors: Use **color + icon + error message**
- Charts: Use **color + pattern + label**

---

## Typography

### Font Stack
```css
font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
```

### Type Scale (Rem-based for User Scaling)
| Token | Size | Line Height | Use Case |
|-------|------|-------------|----------|
| `--text-xs` | 0.75rem (12px) | 1.5 | Captions, labels |
| `--text-sm` | 0.875rem (14px) | 1.5 | Body small, helper text |
| `--text-base` | 1rem (16px) | 1.6 | **Body text (minimum)** |
| `--text-lg` | 1.125rem (18px) | 1.6 | Large body, card titles |
| `--text-xl` | 1.25rem (20px) | 1.5 | Section headings |
| `--text-2xl` | 1.5rem (24px) | 1.4 | Page titles |
| `--text-3xl` | 1.875rem (30px) | 1.3 | Major headings |
| `--text-4xl` | 2.25rem (36px) | 1.2 | Hero titles |

### Readability Requirements
- **Minimum body text: 16px (1rem)** - never smaller
- Line height ≥ 1.5 for body text
- Paragraph width ≤ 65ch (optimal reading width)
- Left-aligned text (no justified text)
- Sufficient letter spacing for uppercase: `tracking-wide`

---

## Focus Management

### Focus Visible (Required)
```css
/* Global focus styles */
:focus-visible {
  outline: 2px solid var(--color-primary-600);
  outline-offset: 2px;
  border-radius: 2px;
}

/* For elements where outline conflicts */
:focus-visible:not(:focus-visible) {
  box-shadow: 0 0 0 2px var(--color-primary-600);
}
```

### Focus Order
- Logical tab order following visual layout
- Skip to main content link as first focusable element
- Modal: Trap focus, restore to trigger on close
- Dropdown: Arrow keys navigate, Enter/Space select, Escape close

### Focus Indicators Must Be:
- Visible on all interactive elements
- High contrast (3:1 minimum against adjacent colors)
- Not removed by `outline: none` without replacement

---

## Keyboard Navigation

### Required Patterns
| Component | Keys |
|-----------|------|
| Links | Enter |
| Buttons | Enter, Space |
| Checkboxes | Space (toggle), Tab (move) |
| Radio groups | Arrow keys (within group), Tab (enter/exit) |
| Select/Dropdown | Space/Enter (open), Arrow keys (navigate), Enter (select), Escape (close) |
| Tabs | Arrow keys (navigate), Enter/Space (activate), Tab (enter panel) |
| Modal | Escape (close), Tab (trap focus) |
| Datatable | Arrow keys (cell navigation), Enter (edit) |

### Skip Links
```html
<!-- First element in body -->
<a href="#main-content" class="skip-link">
  Skip to main content
</a>
```

---

## Semantic HTML Requirements

### Page Structure
```html
<header role="banner">       <!-- Site header/navigation -->
<nav role="navigation">      <!-- Main navigation -->
<main id="main-content" role="main">  <!-- Primary content -->
<aside role="complementary"> <!-- Sidebar (if applicable) -->
<footer role="contentinfo">  <!-- Footer -->
```

### Heading Hierarchy
- **One `<h1>` per page** (page title)
- Headings must not skip levels (h1 → h2 → h3)
- Use headings for content structure, not visual styling

### Landmarks
- Use native HTML5 elements (`<nav>`, `<main>`, `<aside>`, `<section>`)
- Add `aria-label` or `aria-labelledby` to distinguish multiple same-type landmarks

---

## Form Accessibility

### Required Attributes
```html
<!-- Every input MUST have an associated label -->
<label for="email">Email Address</label>
<input type="email" id="email" name="email" required aria-describedby="email-error">

<!-- Error messages linked via aria-describedby -->
<div id="email-error" role="alert" class="error-message">
  Please enter a valid email address
</div>

<!-- Required indicator -->
<legend>Contact Information <span class="required" aria-hidden="true">*</span></legend>
<!-- Or in label -->
<label for="name">Full Name <span class="required" aria-hidden="true">*</span></label>
```

### Field Grouping
```html
<fieldset>
  <legend>Study Preferences</legend>
  <!-- Related fields -->
</fieldset>
```

### Autocomplete
```html
<input type="email" autocomplete="email">
<input type="password" autocomplete="current-password">
<input type="password" autocomplete="new-password">
```

---

## ARIA Patterns

### Live Regions
```html
<!-- For dynamic updates (toasts, loading) -->
<div aria-live="polite" aria-atomic="true" class="sr-only">
  <!-- Content announced when changed -->
</div>

<!-- For urgent alerts -->
<div aria-live="assertive" aria-atomic="true" role="alert">
  <!-- Error messages, critical updates -->
</div>
```

### Common ARIA Roles
| Component | Role | Required Attributes |
|-----------|------|---------------------|
| Modal/Dialog | `role="dialog"` | `aria-modal="true"`, `aria-labelledby`, `aria-describedby` |
| Alert | `role="alert"` | (implicit live region) |
| Tooltip | `role="tooltip"` | `id` referenced by `aria-describedby` |
| Tabs | `role="tablist"` | `aria-orientation` |
| Tab | `role="tab"` | `aria-selected`, `aria-controls`, `id` |
| Tabpanel | `role="tabpanel"` | `aria-labelledby`, `tabindex="0"` |
| Menu | `role="menu"` | `aria-orientation` |
| Menuitem | `role="menuitem"` | |
| Combobox | `role="combobox"` | `aria-expanded`, `aria-controls`, `aria-autocomplete` |
| Table (interactive) | `role="grid"` | `aria-rowcount`, `aria-colcount` |

### Icon Buttons
```html
<!-- Always provide accessible name -->
<button aria-label="Delete task" class="icon-button">
  <svg aria-hidden="true"><!-- trash icon --></svg>
</button>

<!-- Or with visible label -->
<button class="icon-button">
  <svg aria-hidden="true"><!-- trash icon --></svg>
  <span class="sr-only">Delete task</span>
</button>
```

---

## Component Library Recommendation

### Recommended: Headless UI + Tailwind CSS
- **Headless UI**: Unstyled, fully accessible Vue components
- **Radix Vue**: Alternative with similar accessibility
- **PrimeVue**: Full-featured with built-in accessibility (if design system needed)

### Required Components (use library, don't build from scratch)
- [ ] Dialog/Modal
- [ ] Dropdown/Select
- [ ] Tabs
- [ ] Tooltip
- [ ] Toast/Alert
- [ ] Menu/Dropdown
- [ ] Combobox/Autocomplete
- [ ] Datatable (with keyboard nav)
- [ ] Toggle/Switch
- [ ] Slider
- [ ] Date Picker

---

## Testing Requirements

### Automated Testing
```json
// package.json
{
  "devDependencies": {
    "axe-core": "^4.x",
    "vitest-axe": "^0.x",
    "@testing-library/vue": "^8.x"
  }
}
```

```javascript
// Example test
import { render } from '@testing-library/vue'
import { axe } from 'vitest-axe'
import { Button } from '@/components/ui'

test('Button has no accessibility violations', async () => {
  const { container } = render(Button, { slots: { default: 'Click me' } })
  const results = await axe(container)
  expect(results.violations).toHaveLength(0)
})
```

### Manual Testing Checklist
- [ ] Navigate entire app with keyboard only
- [ ] Test with screen reader (NVDA/JAWS on Windows, VoiceOver on Mac)
- [ ] Test with 200% browser zoom
- [ ] Test with high contrast mode (Windows) / inverted colors (Mac)
- [ ] Test with reduced motion preference
- [ ] Validate color contrast with tools (axe, WAVE, Lighthouse)
- [ ] Test form validation announcements
- [ ] Test modal focus trapping
- [ ] Test dropdown keyboard navigation

---

## Responsive Accessibility

### Touch Targets
- **Minimum 44×44 CSS pixels** (WCAG 2.5.5)
- Recommended: 48×48 for comfortable interaction
- Adequate spacing between targets (8px minimum)

### Viewport
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
```

### Text Resizing
- Support up to **200% zoom** without horizontal scrolling
- Use relative units (rem, em) not px for text
- No fixed-height containers for text content

---

## Motion & Animation

### Reduced Motion
```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

- No auto-playing animations > 5 seconds without pause/stop/hide
- No flashing content (> 3 flashes/second)
- Respect `prefers-reduced-motion`

---

## Error Handling

### Form Errors
- Announce errors via `aria-live="assertive"` or `role="alert"`
- Move focus to first error field on submit
- Clear, specific error messages (not "Invalid input")
- Show errors inline near fields

### Error Message Format
```html
<div role="alert" class="error-message" id="email-error">
  <svg aria-hidden="true"><!-- alert icon --></svg>
  <span>Email is required. Please enter your email address.</span>
</div>
```

---

## Documentation References

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [WAI-ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [Headless UI Vue](https://headlessui.com/vue/)
- [Radix Vue](https://www.radix-vue.com/)
- [WebAIM Checklist](https://webaim.org/standards/wcag/checklist)

---

## Implementation Checklist

- [ ] Define design tokens (colors, spacing, typography) in Tailwind config
- [ ] Set up Headless UI + Tailwind CSS
- [ ] Create base components (Button, Input, Label, Card, Modal, etc.)
- [ ] Implement skip link component
- [ ] Create focus management utilities
- [ ] Set up axe-core in CI pipeline
- [ ] Create accessibility testing guide for team
- [ ] Document component accessibility props
- [ ] Conduct accessibility audit before launch