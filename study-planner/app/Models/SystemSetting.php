<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SystemSetting extends Model {
 use HasFactory;
 protected $fillable=['key','value','type','description'];
 public function getTypedValueAttribute(): mixed {
  return match($this->type){'integer'=>(int)$this->value,'boolean'=>filter_var($this->value,FILTER_VALIDATE_BOOLEAN),'float'=>(float)$this->value,'json'=>json_decode($this->value,true),default=>$this->value};
 }
}
