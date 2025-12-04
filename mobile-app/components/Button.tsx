/**
 * Button Component - ปุ่มสำหรับใช้ทั่วไป
 */

import React from 'react';
import { Pressable, Text, ActivityIndicator, View } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

interface ButtonProps {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  loading?: boolean;
  disabled?: boolean;
  icon?: React.ReactNode;
  fullWidth?: boolean;
  className?: string;
}

export const Button: React.FC<ButtonProps> = ({
  title,
  onPress,
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  icon,
  fullWidth = false,
  className = '',
}) => {
  const isDisabled = disabled || loading;

  // Size classes
  const sizeClasses = {
    sm: 'px-3 py-2',
    md: 'px-5 py-3',
    lg: 'px-6 py-4',
  };

  const textSizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
  };

  // Variant styles
  const getVariantStyles = () => {
    switch (variant) {
      case 'primary':
        return {
          gradient: true,
          colors: ['#3B82F6', '#1D4ED8'] as [string, string],
          textColor: 'text-white',
        };
      case 'secondary':
        return {
          gradient: true,
          colors: ['#10B981', '#059669'] as [string, string],
          textColor: 'text-white',
        };
      case 'outline':
        return {
          gradient: false,
          bgClass: 'bg-transparent border-2 border-primary-500',
          textColor: 'text-primary-500',
        };
      case 'ghost':
        return {
          gradient: false,
          bgClass: 'bg-transparent',
          textColor: 'text-primary-500',
        };
      default:
        return {
          gradient: true,
          colors: ['#3B82F6', '#1D4ED8'] as [string, string],
          textColor: 'text-white',
        };
    }
  };

  const styles = getVariantStyles();
  const widthClass = fullWidth ? 'w-full' : '';

  const content = (
    <View className="flex-row items-center justify-center">
      {loading ? (
        <ActivityIndicator
          color={variant === 'outline' || variant === 'ghost' ? '#3B82F6' : '#FFFFFF'}
          size="small"
        />
      ) : (
        <>
          {icon && <View className="mr-2">{icon}</View>}
          <Text
            className={`font-bold ${textSizeClasses[size]} ${styles.textColor}`}
          >
            {title}
          </Text>
        </>
      )}
    </View>
  );

  if (styles.gradient) {
    return (
      <Pressable
        onPress={onPress}
        disabled={isDisabled}
        className={`${widthClass} ${className}`}
        style={({ pressed }) => ({
          opacity: isDisabled ? 0.5 : pressed ? 0.8 : 1,
        })}
      >
        <LinearGradient
          colors={styles.colors!}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          className={`rounded-xl ${sizeClasses[size]}`}
        >
          {content}
        </LinearGradient>
      </Pressable>
    );
  }

  return (
    <Pressable
      onPress={onPress}
      disabled={isDisabled}
      className={`rounded-xl ${sizeClasses[size]} ${styles.bgClass} ${widthClass} ${className}`}
      style={({ pressed }) => ({
        opacity: isDisabled ? 0.5 : pressed ? 0.8 : 1,
      })}
    >
      {content}
    </Pressable>
  );
};
