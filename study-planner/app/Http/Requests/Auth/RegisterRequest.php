<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
class RegisterRequest extends FormRequest {
 public function authorize(): bool{return true;}
 public function rules(): array{return [
  'name'=>['required','string','max:100'],
  'email'=>['required','email','max:255','unique:users,email'],
  'password'=>['required','confirmed',Password::min(8)->mixedCase()->numbers()->symbols()],
  'course'=>['nullable','string','max:150'],
  'year_level'=>['nullable','integer','between:1,8'],
 ];}

 public function messages(): array {
  return [
   'name.required' => 'Please enter your full name.',
   'email.required' => 'An email address is required.',
   'email.email' => 'Please enter a valid email address format (e.g. student@university.edu).',
   'email.unique' => 'This email is already registered. Try signing in instead.',
   'password.required' => 'A password is required.',
   'password.confirmed' => 'Password confirmation does not match the password entered above.',
   'password.min' => 'Password must contain at least 8 characters.',
   'password.mixed_case' => 'Password must include both uppercase (A-Z) and lowercase (a-z) letters.',
   'password.numbers' => 'Password must contain at least one digit (0-9).',
   'password.symbols' => 'Password must contain at least one special symbol (!@#$%^&*).',
  ];
 }
}
