/**
 * Input Component - ช่องกรอกข้อมูล
 */

import React, { useState } from 'react';
import { View, Text, TextInput, Pressable, TextInputProps } from 'react-native';

interface InputProps extends TextInputProps {
  label?: string;
  error?: string;
  hint?: string;
  leftIcon?: string;
  rightIcon?: string;
  onRightIconPress?: () => void;
  isPassword?: boolean;
}

export const Input: React.FC<InputProps> = ({
  label,
  error,
  hint,
  leftIcon,
  rightIcon,
  onRightIconPress,
  isPassword = false,
  className = '',
  ...props
}) => {
  const [isFocused, setIsFocused] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const borderColor = error
    ? 'border-error'
    : isFocused
    ? 'border-primary-500'
    : 'border-gray-300 dark:border-gray-600';

  return (
    <View className={`mb-4 ${className}`}>
      {/* Label */}
      {label && (
        <Text className="text-gray-700 dark:text-gray-300 font-medium mb-1.5">
          {label}
        </Text>
      )}

      {/* Input Container */}
      <View
        className={`flex-row items-center bg-white dark:bg-dark-50 border-2 rounded-xl px-4 ${borderColor}`}
      >
        {/* Left Icon */}
        {leftIcon && (
          <Text style={{ fontSize: 20, marginRight: 10 }}>
            {leftIcon}
          </Text>
        )}

        {/* Text Input */}
        <TextInput
          {...props}
          secureTextEntry={isPassword && !showPassword}
          onFocus={(e) => {
            setIsFocused(true);
            props.onFocus?.(e);
          }}
          onBlur={(e) => {
            setIsFocused(false);
            props.onBlur?.(e);
          }}
          className="flex-1 py-3.5 text-base text-gray-900 dark:text-white"
          placeholderTextColor="#9CA3AF"
        />

        {/* Right Icon / Password Toggle */}
        {isPassword ? (
          <Pressable onPress={() => setShowPassword(!showPassword)}>
            <Text style={{ fontSize: 20 }}>
              {showPassword ? '🙈' : '👁️'}
            </Text>
          </Pressable>
        ) : rightIcon ? (
          <Pressable onPress={onRightIconPress}>
            <Text style={{ fontSize: 20 }}>
              {rightIcon}
            </Text>
          </Pressable>
        ) : null}
      </View>

      {/* Error Message */}
      {error && (
        <Text className="text-error text-sm mt-1">{error}</Text>
      )}

      {/* Hint */}
      {hint && !error && (
        <Text className="text-gray-500 text-sm mt-1">{hint}</Text>
      )}
    </View>
  );
};
