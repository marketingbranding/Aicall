<?php

namespace App\Services\Director;

class TransitionRuleProvider
{
    public function getBaseTransition(RoleplayEventType $type): StateTransition
    {
        return match ($type) {
            // Sales Communication
            RoleplayEventType::GOOD_OPENING => new StateTransition(trustDelta: 3, engagementDelta: 3),
            RoleplayEventType::WEAK_OPENING => new StateTransition(trustDelta: -2),
            RoleplayEventType::ACTIVE_LISTENING => new StateTransition(trustDelta: 3, engagementDelta: 3),
            RoleplayEventType::INTERRUPTED_CUSTOMER => new StateTransition(irritationDelta: 5, engagementDelta: -3),
            RoleplayEventType::REPEATED_INTERRUPTION => new StateTransition(irritationDelta: 8, engagementDelta: -5),
            RoleplayEventType::CLEAR_EXPLANATION => new StateTransition(confusionDelta: -5, trustDelta: 2),
            RoleplayEventType::CONFUSING_EXPLANATION => new StateTransition(confusionDelta: 5, trustDelta: -2),
            RoleplayEventType::GENERIC_SALES_SCRIPT => new StateTransition(engagementDelta: -3, trustDelta: -2),
            RoleplayEventType::RELEVANT_FOLLOW_UP => new StateTransition(trustDelta: 2, engagementDelta: 3),
            RoleplayEventType::MISSED_FOLLOW_UP => new StateTransition(irritationDelta: 3, trustDelta: -2),
            RoleplayEventType::EMPATHIC_RESPONSE => new StateTransition(trustDelta: 4, engagementDelta: 3),
            RoleplayEventType::DISMISSED_CONCERN => new StateTransition(trustDelta: -5, engagementDelta: -5),
            RoleplayEventType::UNSUPPORTED_CLAIM => new StateTransition(trustDelta: -5, irritationDelta: 3),
            RoleplayEventType::CONTRADICTORY_STATEMENT => new StateTransition(trustDelta: -4, confusionDelta: 5),
            RoleplayEventType::AGGRESSIVE_CLOSING => new StateTransition(pressurePerceptionDelta: 8, engagementDelta: -3),
            RoleplayEventType::APPROPRIATE_NEXT_STEP => new StateTransition(trustDelta: 3, engagementDelta: 2),
            RoleplayEventType::CHANGED_TOPIC_TOO_EARLY => new StateTransition(irritationDelta: 4, engagementDelta: -3),

            // Customer Concerns
            RoleplayEventType::CONCERN_DISCOVERED => new StateTransition(trustDelta: 2),
            RoleplayEventType::OBJECTION_TRIGGERED => new StateTransition(anxietyDelta: 3),
            RoleplayEventType::OBJECTION_ACKNOWLEDGED => new StateTransition(trustDelta: 2, irritationDelta: -2),
            RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED => new StateTransition(trustDelta: 3, anxietyDelta: -3),
            RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE => new StateTransition(trustDelta: 2, anxietyDelta: -2),
            RoleplayEventType::MISCONCEPTION_CHALLENGED => new StateTransition(confusionDelta: -3),
            RoleplayEventType::MISCONCEPTION_CLARIFIED_CANDIDATE => new StateTransition(trustDelta: 2, confusionDelta: -5),

            // Boundary
            RoleplayEventType::CUSTOMER_BOUNDARY_TEST => new StateTransition(),
            RoleplayEventType::SALESPERSON_PARTICIPATED_PERSONALLY => new StateTransition(),
            RoleplayEventType::INDIRECT_REDIRECTION => new StateTransition(irritationDelta: 2),
            RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION => new StateTransition(trustDelta: 3),
            RoleplayEventType::EXPLICIT_BOUNDARY_SET => new StateTransition(trustDelta: 2),
            RoleplayEventType::CUSTOMER_RESPECTED_BOUNDARY => new StateTransition(trustDelta: 2),
            RoleplayEventType::CUSTOMER_REPEATED_BOUNDARY_TEST => new StateTransition(irritationDelta: 3),
            RoleplayEventType::SIGNIFICANT_BOUNDARY_VIOLATION => new StateTransition(engagementDelta: -5),

            // Conversation
            RoleplayEventType::CUSTOMER_BECAME_MORE_ENGAGED => new StateTransition(engagementDelta: 8),
            RoleplayEventType::CUSTOMER_BECAME_LESS_ENGAGED => new StateTransition(engagementDelta: -8),
            RoleplayEventType::CUSTOMER_CONFUSED => new StateTransition(confusionDelta: 5),
            RoleplayEventType::CUSTOMER_PRESSURED => new StateTransition(pressurePerceptionDelta: 5),
            RoleplayEventType::TRUST_SIGNAL => new StateTransition(trustDelta: 5),
            RoleplayEventType::DISTRUST_SIGNAL => new StateTransition(trustDelta: -5),
        };
    }
}
