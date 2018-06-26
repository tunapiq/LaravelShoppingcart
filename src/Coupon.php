<?php

namespace Gloudemans\Shoppingcart;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class Coupon
{
  /**
   * discount for items
   *
   * @var string
   */
  private $discount = '0';

  /**
   * Items this coupon is applicable to
   *
   * @var string(csv|'*'|null)
   */
  private $itemids = null;

  /**
   * Expiry date for this coupon
   *
   * @var string
   */
  private $expiry = 2005;

  /**
   * display name for this coupon
   *
   * @var string
   */
  private $name = "";

  /**
   * apply coupon when purchase is greater than or equal to
   *
   * @var float|null
   */
  private $startdiscountat = null;

  /**
   * Cart constructor.
   *
   * @param \Illuminate\Support\Collection  $coupon
   */
  public function __construct($coupon)
  {
    $coupon = $coupon?$coupon:new Collection();
    $this -> discount = $coupon -> has('discount')?$coupon -> get('discount'):$this -> discount;
    $this -> itemids = $coupon -> has('itemids')?$coupon -> get('itemids'):$this -> itemids;
    $this -> expiry = $coupon -> has('expiry')?Carbon::createFromFormat('Y-m-d', $coupon -> get('expiry')):Carbon::createFromDate($this -> expiry);
    $this -> name = $coupon -> has('name')?$coupon -> get('name'):$this -> name;
    $this -> startdiscountat = $coupon -> has('startdiscountat')
    ?$coupon -> get('startdiscountat'):$this -> startdiscountat;

    //make sure user does not provide both fields, if they do, ignore one
    if(!empty($this -> startdiscountat)){
      $this -> itemids = null;
    }
    if(!empty($this -> itemids)){
      $this -> startdiscountat = null;
    }

  }


  public function isNotExpired(){
    if($this -> expiry -> isPast()){
      $this -> name = "";
      return false;
    }
    return true;
  }
  private function claculateDiscount(CartItem $cartItem, $cart_item_count){
    $percent_discount = null;
    $fixed_discount   = null;
    $discount_price = 0;
    $price = $cartItem->qty * $cartItem->priceTax;
    $required_price = floatval($this -> startdiscountat);
    if(ends_with($this -> discount, '%'))
    {
      $percent_discount = floatval(substr($this -> discount, 0, -1))/100;
    }else{
      $fixed_discount = floatval($this -> discount);
    }
    //if discount applies to all items or if there is a satisfied required price for discount
    if($this -> itemids == '*' || $required_price){
      $discount_price = $percent_discount?$price - ($percent_discount * $price):$price - ($fixed_discount/$cart_item_count);
    }
    //if discount applies to certain items only
    else if(in_array($cartItem -> id, explode(',', $this -> itemids))){
      $discount_price = $percent_discount?$price - ($percent_discount * $price):$price - ($fixed_discount * $cartItem->qty);
    }
    //else dont give any discount for item
    else{
      $discount_price = $price;
    }
    return $discount_price;
  }

  public function calculatePriceForItem(CartItem $cartItem, $cart_total, $cart_item_count){
    $price = $cartItem->qty * $cartItem->priceTax;
    $required_price = floatval($this -> startdiscountat);
    //check if item can get discounted price
    if(!empty($required_price) && $cart_total >= $required_price || !empty($this -> itemids)){
      return $this -> claculateDiscount($cartItem, $cart_item_count);
   }
    //otherwise return regular price for item
    return $price;
  }

  public function getName(){
    return $this -> name;
  }

}
