/** Format an instant for a datetime-local field without changing its timezone. */
export function localDeadline(value: string | Date): string {
  const date = new Date(value);
  const pad = (part: number) => String(part).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Include an explicit UTC offset when sending local input to the server. */
export function deadlineInstant(value: string): string {
  const date = new Date(value);
  if (!Number.isFinite(date.getTime()) || localDeadline(date) !== value) {
    throw new Error('Choose a valid local deadline. This time may not exist due to daylight saving.');
  }
  return date.toISOString();
}
