import { ZxcvbnFactory } from '@zxcvbn-ts/core';
import * as common from '@zxcvbn-ts/language-common';
import * as english from '@zxcvbn-ts/language-en';

const estimator = new ZxcvbnFactory({
  translations: english.translations,
  graphs: common.adjacencyGraphs,
  dictionary: { ...common.dictionary, ...english.dictionary },
});

export function passwordStrength(password: string, inputs: string[] = []) {
  const result = estimator.check(password.slice(0, 128), inputs);
  const level = password.length < 15 ? 0 : Math.max(0, result.score - 1);
  return {
    label: ['Weak', 'Average', 'Good', 'Strong'][level],
    color: ['#b91c1c', '#c2410c', '#1d4ed8', '#15803d'][level],
    percent: password ? (level + 1) * 25 : 0,
    suggestion: password.length < 15 ? 'Use at least 15 characters; try several unrelated words.' :
      result.feedback.warning || result.feedback.suggestions[0] || 'Use a unique password saved in a password manager.',
  };
}
