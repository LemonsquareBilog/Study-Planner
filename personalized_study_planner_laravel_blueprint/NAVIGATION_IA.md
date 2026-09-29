# Navigation Information Architecture
## Personalized Study Planner - Vue 3 Frontend

---

## Navigation Hierarchy

### Primary Navigation (Always Visible - Sidebar/Bottom Nav on Mobile)
These are the core features students use daily:

| Order | Item | Route | Icon | Description |
|-------|------|-------|------|-------------|
| 1 | **Dashboard** | `/student/dashboard` | Home | Overview of progress, upcoming tasks, study sessions, recommendations |
| 2 | **Study Plan** | `/student/study-plan` | Calendar | View and manage generated study sessions |
| 3 | **Tasks** | `/student/tasks` | CheckSquare | Manage assignments, projects, exams, readings |

### Secondary Navigation (Collapsible Section)
Features used regularly but not daily:

| Order | Item | Route | Icon | Description |
|-------|------|-------|------|-------------|
| 4 | **Subjects** | `/student/subjects` | BookOpen | Manage subjects and topics |
| 5 | **Quizzes** | `/student/quizzes` | HelpCircle | Take practice quizzes |
| 6 | **Progress** | `/student/progress` | BarChart | Detailed analytics and performance |

### Tertiary Navigation (User Menu / Settings)
Accessed via user avatar dropdown:

| Order | Item | Route | Icon | Description |
|-------|------|-------|------|-------------|
| 7 | **Recommendations** | `/student/recommendations` | Lightbulb | Personalized resource suggestions |
| 8 | **Notifications** | (Bell icon in header) | Bell | Notification center |
| 9 | **Study Preferences** | `/student/settings#preferences` | Clock | Study hours, session length, breaks |
| 10 | **Profile** | `/student/settings#profile` | User | Name, course, year level, password |
| 11 | **Logout** | - | LogOut | Sign out |

---

## Admin Navigation (Separate Admin Layout)

### Primary Admin Navigation

| Order | Item | Route | Icon | Description |
|-------|------|-------|------|-------------|
| 1 | **Dashboard** | `/admin/dashboard` | LayoutDashboard | System metrics overview |
| 2 | **Resources** | `/admin/resources` | Library | Manage learning resources |
| 3 | **Users** | `/admin/users` | Users | Manage student accounts |
| 4 | **Settings** | `/admin/settings` | Settings | System configuration |

---

## Mobile Navigation Pattern

### Bottom Navigation Bar (4 items max)
1. **Dashboard** - Home icon
2. **Study Plan** - Calendar icon  
3. **Tasks** - CheckSquare icon
4. **More** - Menu/Hamburger icon (opens drawer with remaining items)

### Drawer Menu (from "More")
- Subjects
- Quizzes
- Progress
- Recommendations
- Notifications (badge count)
- Settings (Study Preferences, Profile)
- Logout

---

## Active State Indicators

- **Sidebar**: Highlighted background + left border accent
- **Bottom Nav**: Filled icon + label color change
- **Breadcrumbs**: Current page highlighted, non-clickable

---

## Breadcrumb Strategy

| Page | Breadcrumb Trail |
|------|------------------|
| Dashboard | Home / Dashboard |
| Study Plan | Home / Study Plan |
| Study Plan > Session Detail | Home / Study Plan / Session Detail |
| Tasks | Home / Tasks |
| Tasks > Task Detail | Home / Tasks / Task Title |
| Subjects | Home / Subjects |
| Subjects > Subject Detail | Home / Subjects / Subject Name |
| Subject > Topics | Home / Subjects / Subject Name / Topics |
| Quizzes | Home / Quizzes |
| Quizzes > Quiz Taking | Home / Quizzes / Quiz Title |
| Progress | Home / Progress |
| Recommendations | Home / Recommendations |
| Settings | Home / Settings |

---

## Responsive Breakpoints

| Breakpoint | Layout |
|------------|--------|
| `< 640px` (Mobile) | Bottom nav + drawer |
| `640px - 1024px` (Tablet) | Collapsible sidebar + top header |
| `> 1024px` (Desktop) | Persistent sidebar + top header |

---

## Accessibility Notes

- All navigation items must have `aria-current="page"` on active item
- Mobile drawer must trap focus when open
- Skip link to main content on all pages
- Keyboard navigation: Tab between items, Enter/Space to activate
- Screen reader labels for icon-only buttons (mobile bottom nav)

---

## Implementation Checklist

- [ ] Create `NavigationConfig` composable with hierarchy data
- [ ] Build `SidebarNavigation` component (desktop)
- [ ] Build `BottomNavigation` component (mobile)
- [ ] Build `MobileDrawer` component
- [ ] Build `Breadcrumb` component
- [ ] Add `useActiveRoute` composable for active states
- [ ] Implement responsive breakpoint watcher
- [ ] Add keyboard navigation support
- [ ] Add focus management for drawer
- [ ] Test with screen readers (NVDA, VoiceOver)