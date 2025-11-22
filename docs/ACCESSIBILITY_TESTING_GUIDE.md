# Accessibility Testing Guide

## Overview

This guide provides step-by-step instructions for testing the accessibility of the admin Livewire CRUD interface.

## Quick Start

### Automated Testing

1. **Lighthouse Accessibility Audit**
   ```bash
   # Open Chrome DevTools
   # Navigate to Lighthouse tab
   # Select "Accessibility" category
   # Run audit
   ```

2. **axe DevTools**
   ```bash
   # Install axe DevTools browser extension
   # Open extension on admin pages
   # Run automated scan
   # Review and fix issues
   ```

3. **WAVE Browser Extension**
   ```bash
   # Install WAVE extension
   # Navigate to admin pages
   # Click WAVE icon
   # Review accessibility errors and warnings
   ```

## Manual Testing

### Keyboard Navigation Testing

#### Test 1: Tab Navigation
1. Navigate to any admin page
2. Press Tab repeatedly
3. Verify:
   - All interactive elements are reachable
   - Tab order is logical (left-to-right, top-to-bottom)
   - Focus indicator is visible on all elements
   - No keyboard traps (can tab out of all components)

#### Test 2: Modal Navigation
1. Open a create/edit modal
2. Press Tab
3. Verify:
   - Focus is trapped within modal
   - Can tab through all form fields
   - Can reach close button
   - Pressing Escape closes modal
   - Focus returns to trigger button after close

#### Test 3: Inline Editing
1. Navigate to categories page
2. Tab to a category name
3. Press Enter to edit
4. Verify:
   - Edit mode activates
   - Input receives focus
   - Enter saves changes
   - Escape cancels edit

#### Test 4: Search and Filters
1. Tab to search input
2. Type search term
3. Press Enter
4. Verify:
   - Search executes
   - Results update
   - Focus remains in search field

#### Test 5: Bulk Actions
1. Tab to checkbox
2. Press Space to select
3. Tab to bulk action button
4. Press Enter
5. Verify:
   - Checkbox toggles
   - Bulk actions appear
   - Actions execute correctly

### Screen Reader Testing

#### NVDA (Windows)
1. Install NVDA (free)
2. Start NVDA (Ctrl+Alt+N)
3. Navigate to admin page
4. Test:
   - Page title is announced
   - Headings are announced correctly
   - Form labels are associated with inputs
   - Button purposes are clear
   - Table structure is announced
   - Error messages are announced
   - Success messages are announced

#### VoiceOver (macOS)
1. Enable VoiceOver (Cmd+F5)
2. Navigate to admin page
3. Use VoiceOver commands:
   - VO+Right Arrow: Next item
   - VO+Left Arrow: Previous item
   - VO+Space: Activate
   - VO+A: Read all
4. Verify same items as NVDA

#### Testing Checklist
- [ ] Skip links are announced and functional
- [ ] Page title describes current page
- [ ] All images have alt text
- [ ] All buttons have accessible names
- [ ] Form inputs have labels
- [ ] Error messages are announced
- [ ] Success messages are announced
- [ ] Loading states are announced
- [ ] Table headers are announced
- [ ] Sort state is announced
- [ ] Selection state is announced

### Visual Testing

#### Test 1: Focus Indicators
1. Navigate with keyboard
2. Verify:
   - Focus ring is visible on all elements
   - Focus ring has sufficient contrast (3:1 minimum)
   - Focus ring is not obscured by other elements

#### Test 2: Color Contrast
1. Use browser DevTools or contrast checker
2. Test all text against backgrounds
3. Verify:
   - Normal text: 4.5:1 minimum
   - Large text (18pt+): 3:1 minimum
   - UI components: 3:1 minimum

#### Test 3: Zoom Testing
1. Set browser zoom to 200%
2. Navigate through all pages
3. Verify:
   - All content is visible
   - No horizontal scrolling required
   - Text doesn't overlap
   - Interactive elements remain usable

#### Test 4: Touch Target Size
1. Use browser DevTools to measure
2. Verify all interactive elements:
   - Minimum 44x44 pixels
   - Adequate spacing between targets
   - Easy to tap on mobile devices

## Browser Testing Matrix

Test in the following browsers:

| Browser | Version | Platform | Priority |
|---------|---------|----------|----------|
| Chrome | Latest | Windows/Mac | High |
| Firefox | Latest | Windows/Mac | High |
| Safari | Latest | Mac | High |
| Edge | Latest | Windows | Medium |
| Mobile Safari | Latest | iOS | Medium |
| Chrome Mobile | Latest | Android | Medium |

## Assistive Technology Testing Matrix

| Technology | Platform | Priority |
|------------|----------|----------|
| NVDA | Windows | High |
| JAWS | Windows | Medium |
| VoiceOver | macOS | High |
| VoiceOver | iOS | Medium |
| TalkBack | Android | Low |

## Common Issues and Fixes

### Issue: Focus not visible
**Fix**: Add `focus-visible:ring-2 focus-visible:ring-indigo-500` classes

### Issue: Button has no accessible name
**Fix**: Add `aria-label` attribute or visible text

### Issue: Form input has no label
**Fix**: Add `<label>` element with `for` attribute matching input `id`

### Issue: Error message not announced
**Fix**: Add `aria-describedby` linking input to error message

### Issue: Modal doesn't trap focus
**Fix**: Add `x-trap` directive to modal container

### Issue: Table structure not announced
**Fix**: Use proper `<th>` elements with `scope` attributes

### Issue: Loading state not announced
**Fix**: Add `aria-live="polite"` to loading indicator

### Issue: Sort state not announced
**Fix**: Update `aria-label` on sort button to include direction

## Continuous Monitoring

### Automated CI/CD Checks
```bash
# Add to CI pipeline
npm run test:a11y

# Or use pa11y
pa11y http://localhost/admin/posts
pa11y http://localhost/admin/categories
pa11y http://localhost/admin/comments
pa11y http://localhost/admin/users
```

### Regular Audits
- Run Lighthouse audit monthly
- Test with screen readers quarterly
- Review WCAG compliance annually
- Update documentation as needed

## Resources

- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WebAIM Articles](https://webaim.org/articles/)
- [A11y Project Checklist](https://www.a11yproject.com/checklist/)
- [Deque University](https://dequeuniversity.com/)

## Support

For accessibility questions or issues:
1. Check this guide first
2. Review WCAG guidelines
3. Test with assistive technology
4. Consult with accessibility experts if needed
